<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocsController extends AbstractController
{
    #[Route('/docs', name: 'app_docs', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('docs/index.html.twig', [
            'groups' => $this->buildGroups(),
        ]);
    }

    /**
     * @return array<int, array{title: string, description: string, endpoints: array<int, array<string, mixed>>}>
     */
    private function buildGroups(): array
    {
        return [
            [
                'title' => 'Email Box',
                'description' => 'Create, look up and validate temporary email boxes.',
                'endpoints' => [
                    [
                        'id' => 'create-random',
                        'method' => 'POST',
                        'path' => '/api/email-box',
                        'summary' => 'Create a new random temporary email box.',
                        'auth' => false,
                        'request_body' => null,
                        'response' => [
                            'email' => 'fluffy.kitten.4821@example.com',
                            'uuid' => '0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d',
                        ],
                        'curl' => "curl -X POST {host}/api/email-box",
                    ],
                    [
                        'id' => 'create-custom',
                        'method' => 'POST',
                        'path' => '/api/email-box/custom',
                        'summary' => 'Find or create a mailbox by custom name. Re-using the same name returns the same mailbox.',
                        'auth' => false,
                        'request_body' => [
                            'name' => 'team-alpha',
                        ],
                        'response' => [
                            'email' => 'team-alpha@example.com',
                            'uuid' => '0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d',
                        ],
                        'notes' => 'Name: 1-64 chars, letters / digits / "." / "_" / "-". Domain is fixed to the first active domain.',
                        'curl' => "curl -X POST {host}/api/email-box/custom \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"name\":\"team-alpha\"}'",
                    ],
                    [
                        'id' => 'validate',
                        'method' => 'POST',
                        'path' => '/api/email-box/validate',
                        'summary' => 'Validate an email + uuid pair (used to verify a mailbox saved in localStorage still exists).',
                        'auth' => false,
                        'request_body' => [
                            'email' => 'team-alpha@example.com',
                            'uuid' => '0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d',
                        ],
                        'response' => [
                            'is_valid' => true,
                        ],
                        'curl' => "curl -X POST {host}/api/email-box/validate \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"email\":\"team-alpha@example.com\",\"uuid\":\"<uuid>\"}'",
                    ],
                ],
            ],
            [
                'title' => 'Messages',
                'description' => 'List and read received messages of a mailbox.',
                'endpoints' => [
                    [
                        'id' => 'list-messages',
                        'method' => 'GET',
                        'path' => '/api/email-box/{emailBoxUuid}/emails',
                        'summary' => 'List all messages received by a mailbox. Marks all subjects as read.',
                        'auth' => false,
                        'request_body' => null,
                        'response' => [[
                            'uuid' => '0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d',
                            'from' => 'sender@partner.com',
                            'real_to' => 'team-alpha@example.com',
                            'from_name' => 'Sender',
                            'subject' => 'Welcome!',
                            'received_at' => '2026-05-15T10:30:00+00:00',
                        ]],
                        'curl' => "curl {host}/api/email-box/<emailBoxUuid>/emails",
                    ],
                    [
                        'id' => 'get-message',
                        'method' => 'GET',
                        'path' => '/api/email-box/{emailBoxUuid}/email/{emailUuid}',
                        'summary' => 'Get the full content of a single message (HTML body included). Marks the message as read.',
                        'auth' => false,
                        'request_body' => null,
                        'response' => [
                            'uuid' => '0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d',
                            'from' => 'sender@partner.com',
                            'real_to' => 'team-alpha@example.com',
                            'from_name' => 'Sender',
                            'subject' => 'Welcome!',
                            'html' => '<p>Hello!</p>',
                            'received_at' => '2026-05-15T10:30:00+00:00',
                        ],
                        'curl' => "curl {host}/api/email-box/<emailBoxUuid>/email/<emailUuid>",
                    ],
                    [
                        'id' => 'view-message',
                        'method' => 'GET',
                        'path' => '/email-box/{emailBoxUuid}/email/{emailUuid}',
                        'summary' => 'Render a single message as an HTML page (used by the "View Full Screen" button).',
                        'auth' => false,
                        'request_body' => null,
                        'response' => '<HTML page>',
                        'curl' => "curl {host}/email-box/<emailBoxUuid>/email/<emailUuid>",
                    ],
                ],
            ],
            [
                'title' => 'Inbound (webhook)',
                'description' => 'Used by the SMTP gateway to deliver incoming mail into the system.',
                'endpoints' => [
                    [
                        'id' => 'receive-email',
                        'method' => 'POST',
                        'path' => '/api/email',
                        'summary' => 'Webhook endpoint that ingests an incoming email.',
                        'auth' => true,
                        'request_body' => [
                            'real_from' => 'sender@partner.com',
                            'real_to' => 'team-alpha@example.com',
                            'subject' => 'Welcome!',
                            'from_name' => 'Sender',
                            'from_address' => 'sender@partner.com',
                            'to_multiple' => ['team-alpha@example.com'],
                            'bcc_multiple' => null,
                            'html' => '<p>Hello!</p>',
                            'metadata' => null,
                        ],
                        'response' => '"OK"',
                        'notes' => 'Protected by CreateReceivedEmailAuthValidator – requires the gateway auth header.',
                        'curl' => "curl -X POST {host}/api/email \\\n  -H 'Content-Type: application/json' \\\n  -H 'Authorization: Bearer <gateway-token>' \\\n  -d '{...}'",
                    ],
                ],
            ],
        ];
    }
}
