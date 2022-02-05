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
                if (
                    $definition instanceof \GraphQL\Language\AST\OperationDefinitionNode && strtolower($definition->operation) === "subscription"
                    && $definition->name->value === $operations->operation
                )
                    throw new \Exception("Subs not allowed over non stream connections");
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

    private function heartBeatMessage() {
        echo "data:\n\n";
        ob_flush();
        flush();
    }

    private function endOfStreamMessage() {
        echo "data: END-OF-STREAM\n\n";
        ob_flush();
        flush();
    }

    private function streamData(?string $key, mixed $data)
    {
        $arr = $key ? ['data' => [$key => $data]] : $data;
        echo 'data: ' . json_encode($arr) . "\n\n";
        ob_flush();
        flush();
    }

    private function subscriptionStream($data): StreamedResponse
    {
        return response()->stream(function () use ($data) {
            try {
                $timeStart = microtime(true);
                if (array_key_exists('errors', $data)) {
                    $this->streamData(null, $data);
                    return;
                }
                $curr = ['data' => []];
                foreach ($data['data'] as $key => $value) {
                    if (is_callable($value)) continue;
                    $curr['data'][$key] = $value;
                    unset($data['data'][$key]);
                }
                if (count($curr['data']) > 0) $this->streamData(null, $curr);
                if (count($data['data']) === 0) return null;
            
                while (true) {
                    if (connection_aborted() || microtime(true) - $timeStart >= 60) {
                        break;
                    }
                    foreach ($data['data'] as $key => $value) {
                        $result = $value();
                        if ($result) $this->streamData($key, $result);
                    }
                    sleep(1);
                    // $this->heartBeatMessage();
                }
            } catch (\Throwable $th) {
                $this->streamData(null, $th);
                return;
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive'
        ]);
    } 
}
