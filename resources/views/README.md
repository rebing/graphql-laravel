# GraphiQL integration

See https://github.com/graphql/graphiql for the home of GraphiQL

The file `graphiql.php` is an integration of the example provided by GraphiQL. It's a slightly modified version from the official repository so it can be integrated.

The modifications are documented here in detail as to replicate them on future updates:

- copy example from packages/graphiql/example/index.html
- adjust CDN URLs from https://cdnjs.com/libraries/graphiql
- graphQLFetcher: replace the hardcoded path with the library configured path:
  `return fetch('/graphql', {` => `return fetch('<?php echo $graphqlPath; ?>', {`
- csrf integration
  - right before `function graphQLFetcher` add: `var xcsrfToken = null;`
  - in the actual `fetch` call, add this header: `'x-csrf-token': xcsrfToken || '<?php echo csrf_token(); ?>'`
  - in the first `then` after the `fetch` call: `xcsrfToken = response.headers.get('x-csrf-token');`
