<!--
 *  Copyright (c) 2021 GraphQL Contributors
 *  All rights reserved.
 *
 *  This source code is licensed under the license found in the
 *  LICENSE file in the root directory of this source tree.
-->
<!DOCTYPE html>
<html>

<head>
  <title><?php echo $schema ? "$schema | " : ''; ?>GraphiQL</title>
  <style>
    body {
      height: 100%;
      margin: 0;
      width: 100%;
      overflow: hidden;
    }

    #graphiql {
      height: 100vh;
    }
  </style>

  <!--
      This GraphiQL example depends on Promise and fetch, which are available in
      modern browsers, but can be "polyfilled" for older browsers.
      GraphiQL itself depends on React DOM.
      If you do not want to rely on a CDN, you can host these files locally or
      include them directly in your favored resource bunder.
    -->
  <script crossorigin src="https://unpkg.com/react@16/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@16/umd/react-dom.development.js"></script>

  <!--
      These two files can be found in the npm module, however you may wish to
      copy them directly into your environment, or perhaps include them in your
      favored resource bundler.
     -->
  <link rel="stylesheet" href="https://unpkg.com/graphiql/graphiql.min.css" />
</head>

<body>
  <div id="graphiql">Loading...</div>
  <script src="https://unpkg.com/graphiql/graphiql.min.js" type="application/javascript"></script>
  <!-- <script src="/renderExample.js" type="application/javascript"></script> -->
  <script>
    var xcsrfToken = null;

    function graphQLFetcher(graphQLParams, data) {
      console.log(data.headers);

      let clientHeaders = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'x-csrf-token': xcsrfToken || '<?php echo csrf_token(); ?>'
      };
      let userHeaders = data.headers;
      let headers = {...clientHeaders, ...userHeaders};

      console.log(headers);
      return fetch(
        '<?php echo $graphqlPath; ?>', {
          method: 'post',
          headers: headers,
          body: JSON.stringify(graphQLParams),
          credentials: 'omit',
        },
      ).then(function(response) {
        xcsrfToken = response.headers.get('x-csrf-token');
        return response.json().catch(function() {
          return response.text();
        });
      });
    }

    ReactDOM.render(
      React.createElement(GraphiQL, {
        fetcher: graphQLFetcher,
        defaultVariableEditorOpen: true,
      }),
      document.getElementById('graphiql'),
    );
  </script>
</body>

</html>