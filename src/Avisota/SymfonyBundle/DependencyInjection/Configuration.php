<?php

/**
 * Avisota newsletter and mailing system - symfony bundle
 *
 * PHP Version 5.3
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota-core
 * @license    LGPL-3.0+
 * @link       http://avisota.org
 */

namespace Avisota\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Validator\Tests\Fixtures\Reference;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode    = $treeBuilder->root('avisota');

		$rootNode
			->children()
				->arrayNode('renderer')
					->requiresAtLeastOneElement()
					->useAttributeAsKey('name')
					->prototype('array')
					->children()
						->scalarNode('type')
							->defaultValue('native')
							->end()
						->end()
					->end()
				->end()
				->arrayNode('queue')
					->requiresAtLeastOneElement()
					->useAttributeAsKey('name')
					->prototype('array')
					->children()
						->scalarNode('type')
							->defaultValue('simple')
							->end()
						->scalarNode('table')
							->end()
						->scalarNode('id')
							->end()
						->end()
					->validate()
						->ifTrue(function($v) { return 'simple' == $v['type'] && empty($v['table']); })
						->thenInvalid('You have to configure a table name for simple queue')
						->end()
					->end()
				->end()
				->arrayNode('transport')
					->requiresAtLeastOneElement()
					->useAttributeAsKey('name')
					->prototype('array')
					->children()
						->scalarNode('type')
							->defaultValue('swift')
							->end()
						->scalarNode('id')
							->defaultValue('mailer')
							->end()
						->scalarNode('renderer')
							->end()
						->end()
					->validate()
						->ifTrue(function($v) { return 'swift' == $v['type'] && empty($v['id']); })
						->thenInvalid('You have to configure the swiftmailer service id')
						->ifTrue(function($v) { return empty($v['renderer']); })
						->thenInvalid('You have to configure the message renderer service id')
						->end()
					->end()
				->end();

		return $treeBuilder;
	}
}
