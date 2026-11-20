<?php

namespace Crafteus\Contracts;

use Crafteus\Environment\Stub;

/**
 * @template T_Stub of \Crafteus\Environment\Stub
 */
interface TemplatingInterface
{
	/**
	 * Constructs Templating instance.
	 *
	 * @param T_Stub $stub
	 */
	public function __construct(Stub $stub);

	/**
	 * Run templating script.
	 *
	 * @return void
	 */
	public function run(): void;
}
