<?php declare(strict_types=1);

namespace Rector\DeadCode\Tests\Rector\Expression\SimplifyMirrorAssignRector;

use Rector\DeadCode\Rector\Expression\SimplifyMirrorAssignRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class SimplifyMirrorAssignRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFiles([__DIR__ . '/Fixture/fixture.php.inc']);
    }

    public function getRectorClass(): string
    {
        return SimplifyMirrorAssignRector::class;
    }
}
