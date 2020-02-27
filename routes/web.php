<?php

$scope = env('SCOPES', 'repo,user');

$host = env('GIT_HOSTNAME', 'https://github.com');
$token_path = env('OAUTH_TOKEN_PATH', '/login/oauth/access_token');
$auth_path = env('OAUTH_AUTHORIZE_PATH', '/login/oauth/authorize');

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId' => env('OAUTH_CLIENT_ID'),
    'clientSecret' => env('OAUTH_CLIENT_SECRET'),
    'urlAuthorize' => $host . $auth_path,
    'urlAccessToken' => $host . $token_path,
    'urlResourceOwnerDetails' => '',
]);

$router->get('/', function () use ($router) {
    return 'Hello<br><a href="/auth">Log in with Github</a>';
});

$router->get('/auth', function () use ($router, $scope, $provider) {
    header('Location: ' . $provider->getAuthorizationUrl(['scope' => $scope]));
    exit;
});

$router->get('/callback', function () use ($router, $scope, $provider) {

    try {
        $code = app('request')->get('code');
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
        $message = 'success';

        $content = json_encode([
            'token' => $token->getToken(),
            'provider' => 'github',
        ]);

    } catch (\Exception $e) {
        $message = 'error';
        $content = $e->getMessage();
    }
    $post_message = sprintf('authorization:github:%s:%s', $message, $content);

    return "<html><body><script>
    (function() {
      function recieveMessage(e) {
        console.log(\"recieveMessage %o\", e)
        // send message to main window with da app
        window.opener.postMessage(
          '$post_message',
          e.origin
        )
      }
      window.addEventListener(\"message\", recieveMessage, false)
      // Start handshare with parent
      console.log(\"Sending message: %o\", \"github\")
      window.opener.postMessage('authorizing:github', '*')
      })()
    </script></body></html>";
});

$router->get('/success', function () use ($router, $scope, $provider) {
    return response('', 204);
});
