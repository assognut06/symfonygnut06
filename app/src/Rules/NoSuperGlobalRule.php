<?php
namespace App\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;


/**
 * @implements Rule<Node\Expr\Variable>
 */

class NoSuperGlobalRule implements Rule {
    public function getNodeType(): string {
        return Node\Expr\Variable::class;
    }

    public function processNode(Node $node, Scope $scope): array {
        // echo "Processing variable node at line " . $node->getLine() . "\n"; // Debug output
        if (is_string($node->name)) {
            // echo "Variable name: " . $node->name . "\n"; // Debug output
            if (in_array($node->name, ['_GET', '_POST', '_SERVER', '_COOKIE', '_FILES', '_ENV', '_REQUEST'], true)) {
                return [RuleErrorBuilder::message('Superglobal $' . $node->name . ' usage is not allowed.')->identifier('superglobal.usage')->build()];
            }
        }
        return [];
    }
}