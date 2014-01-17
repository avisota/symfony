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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class AvisotaExtension
 */
class AvisotaExtension extends Extension
{
	/**
	 * {@inheritDoc}
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = new Configuration();
		$config        = $this->processConfiguration($configuration, $configs);

		foreach ($config['renderer'] as $rendererName => $rendererConfiguration) {
			switch ($rendererConfiguration['type']) {
				default: // case native
					$class = 'Avisota\Renderer\NativeMessageRenderer';
					$args = array();
			}
			$definition = new Definition($class, $args);
			$container->setDefinition('avisota.renderer.' . $rendererName, $definition);
		}

		foreach ($config['queue'] as $queueName => $queueConfiguration) {
			switch ($queueConfiguration['type']) {
				default: // case simple
					$class = 'Avisota\Queue\SimpleDatabaseQueue';
					$args = array(
						new Reference('doctrine.dbal.default_connection'),
						$queueConfiguration['table'],
					);
			}
			$definition = new Definition($class, $args);
			$container->setDefinition('avisota.queue.' . $queueName, $definition);
		}

		foreach ($config['transport'] as $transportName => $transportConfiguration) {
			switch ($transportConfiguration['type']) {
				default: // case swift
					$class = 'Avisota\Transport\SwiftTransport';
					$args = array(
						new Reference($transportConfiguration['id']),
						new Reference('avisota.renderer.' . $transportConfiguration['renderer']),
					);
			}
			$definition = new Definition($class, $args);
			$container->setDefinition('avisota.transport.' . $transportName, $definition);
		}
	}
}
