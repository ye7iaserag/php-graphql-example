<?php

declare(strict_types=1);

namespace App\Http\Controllers;



use Rebing\GraphQL\GraphQLController as Controller;
use GraphQL\Server\OperationParams as BaseOperationParams;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laragraph\Utils\RequestParser;
use Rebing\GraphQL\Support\OperationParams;
use Symfony\Component\HttpFoundation\StreamedResponse;

use GraphQL\Language\Parser;
use GraphQL\Language\Source;

class GraphQLController extends Controller
{
    public function run(Request $request, RequestParser $parser, Repository $config, \Rebing\GraphQL\GraphQL $graphql): JsonResponse | StreamedResponse
    {
        $routePrefix = $config->get('graphql.route.prefix', 'graphql');
        $schemaName = $this->findSchemaNameInRequest($request, "$routePrefix/") ?? $config->get('graphql.default_schema', 'default');

        $operations = $parser->parseRequest($request);

        $headers = $config->get('graphql.headers', []);
        $jsonOptions = $config->get('graphql.json_encoding_options', 0);

        $isBatch = \is_array($operations);

        $isStream = $request->header('Accept') === 'text/event-stream';

        $supportsBatching = $config->get('graphql.batching.enable', true);

        if ($isBatch && !$supportsBatching) {
            $data = $this->createBatchingNotSupportedResponse($request->input());

            return response()->json($data, 200, $headers, $jsonOptions);
        }

        if (!$isStream) {
            foreach ((new OperationParams($operations))->getParsedQuery()->definitions as $definition)
                if ($definition instanceof \GraphQL\Language\AST\OperationDefinitionNode && strtolower($definition->operation) === "subscription") throw new \Exception("Subs not allowed over non stream connections");
        }

        $data = \Rebing\GraphQL\Helpers::applyEach(
            function (BaseOperationParams $baseOperationParams) use ($schemaName, $graphql): array {
                $operationParams = new OperationParams($baseOperationParams);
                return $graphql->execute($schemaName, $operationParams);
            },
            $operations
        );


        if ($isStream)
            return $this->subscriptionStream($data);

        return response()->json($data, 200, $headers, $jsonOptions);
    }

    private function streamData($data) {
        echo 'data: '.json_encode($data)."\n\n";
        ob_flush();
        flush();
    }

    private function subscriptionStream($data): StreamedResponse
    {
        return response()->stream(function () use ($data) {
            if (array_key_exists('errors', $data)) {
                $this->streamData($data);
                return;
            }
            try {
                foreach ($data['data'] as $key => $value) {
                    if (is_callable($value)) continue;
                    $this->streamData($value);
                    unset($data['data'][$key]);
                }
                if (count($data['data']) === 0) return;
            } catch (\Throwable $th) {
                $this->streamData($th);
                return;
            }
            while (true) {
                if (connection_aborted()) {
                    break;
                }
                foreach ($data['data'] as $value) {
                    $result = $value();
                    dd($result);
                    $this->streamData($result);
                }

                sleep(1);
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive'
        ]);
    }

    // public function graphiql(Request $request, Repository $config, Factory $viewFactory): View
    // {
    //     $routePrefix = $config->get('graphql.graphiql.prefix', 'graphiql');
    //     $schemaName = $this->findSchemaNameInRequest($request, "$routePrefix/");

    //     $graphqlPath = '/' . $config->get('graphql.route.prefix', 'graphql');

    //     if ($schemaName) {
    //         $graphqlPath .= '/' . $schemaName;
    //     }

    //     $view = $config->get('graphql.graphiql.view', 'graphql::graphiql');

    //     return $viewFactory->make($view, [
    //         'graphqlPath' => $graphqlPath,
    //         'schema' => $schemaName,
    //     ]);
    // }

    // /**
    //  * In case batching is not supported, send an error back for each batch
    //  * (with a hardcoded limit of 100).
    //  *
    //  * The returned format still matches the GraphQL specs
    //  *
    //  * @param array<string,mixed> $input
    //  * @return array<array{errors:array<array{message:string}>}>
    //  */
    // protected function createBatchingNotSupportedResponse(array $input): array
    // {
    //     $count = min(\count($input), 100);

    //     $data = [];

    //     for ($i = 0; $i < $count; $i++) {
    //         $data[] = [
    //             'errors' => [
    //                 [
    //                     'message' => 'Batch request received but batching is not supported',
    //                 ],
    //             ],
    //         ];
    //     }

    //     return $data;
    // }

    // protected function findSchemaNameInRequest(Request $request, string $routePrefix): ?string
    // {
    //     $path = $request->path();

    //     if (!Str::startsWith($path, $routePrefix)) {
    //         return null;
    //     }

    //     return Str::after($path, $routePrefix);
    // }
}
