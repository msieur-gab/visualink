<?php
// api.php - Admin API endpoints
require_once 'config.php';
checkAuthAPI();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Get code from URL if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$code = isset($pathParts[1]) ? $pathParts[1] : null;

switch ($method) {
    case 'GET':
        // List all redirects
        $redirects = listAllRedirects();
        echo json_encode($redirects);
        break;

    case 'POST':
        // Create new redirect
        if (!isset($input['code']) || !isset($input['targetUrl'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Code and targetUrl required']);
            exit;
        }

        $code = $input['code'];

        if (loadRedirect($code) !== null) {
            http_response_code(409);
            echo json_encode(['error' => 'Code already exists']);
            exit;
        }

        $redirect = [
            'code' => $code,
            'targetUrl' => $input['targetUrl'],
            'description' => $input['description'] ?? '',
            'createdAt' => date('c'),
            'accessCount' => 0,
            'currentUrlScans' => 0,
            'urlHistory' => [
                [
                    'url' => $input['targetUrl'],
                    'startDate' => date('c'),
                    'endDate' => null,
                    'scans' => 0
                ]
            ],
            'accessLog' => []
        ];

        saveRedirect($code, $redirect);
        echo json_encode([
            'success' => true,
            'code' => $code,
            'url' => '/redirect.php?code=' . $code
        ]);
        break;

    case 'PUT':
        // Update redirect
        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Code required']);
            exit;
        }

        $redirect = loadRedirect($code);
        if ($redirect === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Code not found']);
            exit;
        }

        $urlChanged = false;

        if (isset($input['targetUrl']) && $input['targetUrl'] !== $redirect['targetUrl']) {
            // URL is changing - close current history entry and create new one
            $urlChanged = true;

            // Close current URL history entry
            if (!empty($redirect['urlHistory'])) {
                $lastKey = count($redirect['urlHistory']) - 1;
                $redirect['urlHistory'][$lastKey]['endDate'] = date('c');
            }

            // Create new URL history entry
            $redirect['urlHistory'][] = [
                'url' => $input['targetUrl'],
                'startDate' => date('c'),
                'endDate' => null,
                'scans' => 0
            ];

            $redirect['targetUrl'] = $input['targetUrl'];
            $redirect['currentUrlScans'] = 0;
        }

        if (isset($input['description'])) {
            $redirect['description'] = $input['description'];
        }

        $redirect['updatedAt'] = date('c');

        saveRedirect($code, $redirect);
        echo json_encode(['success' => true, 'urlChanged' => $urlChanged]);
        break;

    case 'DELETE':
        // Delete redirect
        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Code required']);
            exit;
        }

        if (!deleteRedirect($code)) {
            http_response_code(404);
            echo json_encode(['error' => 'Code not found']);
            exit;
        }

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
