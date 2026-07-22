<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CommentType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\RulesCaptureQuery;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\RulesPostType;

class VariantsValidationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                RulesCaptureQuery::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            CommentType::class,
            RulesPostType::class,
        ]);
    }

    public function testEveryVariantIsValidated(): void
    {
        // Today only the merged (last-wins) args are validated, so a rule
        // violation in the FIRST alias slips through. With per-variant
        // validation it must fail.
        $result = $this->httpGraphql('{ rulesCapture {
            a: comments(top: 99) { id }
            b: comments(top: 3) { id }
        } }', ['expectErrors' => true]);

        self::assertSame('validation', $result['errors'][0]['message']);

        $validation = $result['errors'][0]['extensions']['validation'];
        self::assertNotEmpty($validation);

        foreach ($validation as $key => $messages) {
            self::assertStringNotContainsString('argsVariants', $key, 'no variant segments may leak into user-facing keys');
            self::assertDoesNotMatchRegularExpression('/[0-9a-f]{32}/', $key);

            foreach ($messages as $message) {
                self::assertStringNotContainsString('argsVariants', $message, 'no variant segments may leak into message text');
                self::assertDoesNotMatchRegularExpression('/[0-9a-f]{32}/', $message);
            }
        }
    }

    public function testAllVariantsValidPasses(): void
    {
        $result = $this->httpGraphql('{ rulesCapture {
            a: comments(top: 1) { id }
            b: comments(top: 2) { id }
        } }');

        self::assertSame(['rulesCapture' => []], $result['data']);
    }

    public function testMergedOnlyPathStillValidates(): void
    {
        // Regression guard: non-variant validation must behave exactly as before.
        $result = $this->httpGraphql('{ rulesCapture {
            comments(top: 99) { id }
        } }', ['expectErrors' => true]);

        self::assertSame('validation', $result['errors'][0]['message']);
    }

    public function testValidationErrorReadsRemappedMessagesThroughDecorator(): void
    {
        // Unit-level guard for the decorator + ValidationError read-through
        // (ValidationError::getValidatorMessages() delegates to the validator).
        $hash = '0123456789abcdef0123456789abcdef';
        $inner = \Illuminate\Support\Facades\Validator::make(
            ['comments' => ['argsVariants' => [$hash => ['args' => ['top' => 99]]]]],
            ["comments.argsVariants.{$hash}.args.top" => 'integer|max:10'],
        );
        self::assertTrue($inner->fails());

        $bag = new \Illuminate\Support\MessageBag;

        foreach ($inner->errors()->messages() as $key => $errors) {
            $cleanKey = \Safe\preg_replace('/\.argsVariants\.[0-9a-f]{32}(?=\.args\.)/', '', $key);

            foreach ($errors as $error) {
                $bag->add($cleanKey, $error);
            }
        }

        $error = new \Rebing\GraphQL\Error\ValidationError(
            'validation',
            new \Rebing\GraphQL\Support\ArgsVariants\RemappedValidator($inner, $bag),
        );

        self::assertSame(['comments.args.top'], $error->getValidatorMessages()->keys());
        self::assertTrue($error->getValidator()->fails());
    }
}
