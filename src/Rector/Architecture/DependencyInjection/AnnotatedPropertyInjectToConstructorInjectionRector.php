<?php declare(strict_types=1);

namespace Rector\Rector\Architecture\DependencyInjection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockManipulator;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * Can cover these cases:
 * - https://doc.nette.org/en/2.4/di-usage#toc-inject-annotations
 * - https://github.com/Kdyby/Autowired/blob/master/docs/en/index.md#autowired-properties
 * - http://jmsyst.com/bundles/JMSDiExtraBundle/master/annotations
 * - https://github.com/rectorphp/rector/issues/700#issue-370301169
 */
final class AnnotatedPropertyInjectToConstructorInjectionRector extends AbstractRector
{
    /**
     * @var string
     */
    private $annotation;

    /**
     * @var DocBlockManipulator
     */
    private $docBlockManipulator;

    public function __construct(DocBlockManipulator $docBlockManipulator, string $annotation)
    {
        $this->docBlockManipulator = $docBlockManipulator;
        $this->annotation = $annotation;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Turns non-private properties with `@annotation` to private properties and constructor injection',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
/**
 * @var SomeService
 * @inject
 */
public $someService;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
/**
 * @var SomeService
 */
private $someService;

public function __construct(SomeService $someService)
{
    $this->someService = $someService;
}
CODE_SAMPLE
                    ,
                    [
                        '$annotation' => 'inject',
                    ]
                ),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->docBlockManipulator->hasTag($node, $this->annotation)) {
            return null;
        }

        // it needs @var tag as well, to get the type
        if (! $this->docBlockManipulator->hasTag($node, 'var')) {
            return null;
        }

        $this->docBlockManipulator->removeTagFromNode($node, $this->annotation);

        // set to private
        $node->flags = Class_::MODIFIER_PRIVATE;

        $this->addPropertyToCollector($node);

        return $node;
    }

    private function addPropertyToCollector(Property $property): void
    {
        $classNode = $property->getAttribute(Attribute::CLASS_NODE);
        if (! $classNode instanceof Class_) {
            return;
        }

        $mainPropertyType = $this->getTypes($property)[0] ?? 'mixed';
        $propertyName = $this->getName($property);
        $this->addPropertyToClass($classNode, $mainPropertyType, $propertyName);
    }
}
