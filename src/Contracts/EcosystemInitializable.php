<?php

namespace Crafteus\Contracts;

use Crafteus\Environment\Ecosystem;

/**
 * Defines the lifecycle hooks invoked when a Template is initialized
 * by an Ecosystem.
 *
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 */
interface EcosystemInitializable
{
	/**
	 * Called immediately before the Ecosystem initializes the Template.
	 *
	 * @param T_Ecosystem $ecosystem The Ecosystem responsible for the initialization.
	 */
	public function beforeInitialize(Ecosystem $ecosystem): void;

	/**
	 * Called immediately after the Ecosystem has finished initializing the Template.
	 *
	 * @param T_Ecosystem $ecosystem The Ecosystem responsible for the initialization.
	 */
	public function afterInitialize(Ecosystem $ecosystem): void;

	/**
	 * Called after the template's configuration has been changed.
	 *
	 * @param array $old_config The configuration before the update.
	 * @param T_Ecosystem $ecosystem The ecosystem that performed the configuration update.
	 */
	public function afterConfigurationChanged(array $old_config, Ecosystem $ecosystem): void;
}
