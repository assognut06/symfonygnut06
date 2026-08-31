<?php

namespace App\Tests\Functional;

final class ChatbotAccessibilityTest extends WebTestCase
{
    public function testChatbotUsesAccessibleBootstrapModalMarkup(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorCount(1, '#openChat');
        $this->assertSelectorExists(
            '#openChat[type="button"]'
            . '[aria-controls="chatBox"]'
            . '[aria-haspopup="dialog"]'
            . '[data-bs-toggle="modal"]'
            . '[data-bs-target="#chatBox"]'
        );

        $this->assertSelectorCount(1, '#chatBox');
        $this->assertSelectorExists(
            '#chatBox.modal[role="dialog"]'
            . '[aria-labelledby="chatbotTitle"]'
            . '[aria-hidden="true"]'
            . '[tabindex="-1"]'
        );
        $this->assertSelectorTextSame('#chatbotTitle', 'Agent IA handicap');

        $this->assertSelectorExists(
            '#chatBox #closeChat[type="button"]'
            . '[aria-label="Fermer le chatbot"]'
            . '[data-bs-dismiss="modal"]'
        );
        $this->assertSelectorExists('#chatBox #chatInput');
    }
}
