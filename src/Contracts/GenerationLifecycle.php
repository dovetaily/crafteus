<?php

namespace Crafteus\Contracts;

/**
 * Defines lifecycle hooks around a generation process.
 *
 * @template T of object|null
 */
interface GenerationLifecycle
{
	/**
	 * Called before the generation process starts.
	 *
	 * @param T $data Data associated with the generation process, if any.
	 * @param string|null $origin The method that initiated the generation process, if known.
	 *
	 * @return void
	 */
	public function beforeGenerate(mixed $data = null, ?string $origin = null): void;

	/**
	 * Called after the generation process completes successfully.
	 *
	 * @param T $data Data associated with the generation process, if any.
	 * @param string|null $origin The method that initiated the generation process, if known.
	 *
	 * @return void
	 */
	public function afterGenerateSuccessfully(mixed $data = null, ?string $origin = null): void;

	/**
	 * Called after the generation process fails.
	 *
	 * @param T $data Data associated with the generation process, if any.
	 * @param string|null $origin The method that initiated the generation process, if known.
	 *
	 * @return void
	 */
	public function afterGenerateFailed(mixed $data = null, ?string $origin = null): void;

	/**
	 * Called after the generation process completes.
	 *
	 * @param T $data Data associated with the generation process, if any.
	 * @param string|null $origin The method that initiated the generation process, if known.
	 *
	 * @return void
	 */
	public function afterGenerate(mixed $data = null, ?string $origin = null): void;
}
