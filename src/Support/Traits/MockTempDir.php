<?php

namespace Crafteus\Support\Traits;

use Crafteus\Support\Helper;

trait MockTempDir
{

	final protected ?string $tempDir;

	public function setTempDir(?string $tempDir): self
	{
		$this->tempDir = is_string($tempDir) ? Helper::normalizePath($tempDir) : $tempDir;

		$info = is_string($this->tempDir) ? Helper::getLastExistingDirectoryInfo($this->tempDir) : null;

		// if($this->tempDir)
		// 	Helper::dd($this->tempDir, $info);

		if (is_string($this->tempDir) && !$info['is_writable']) {
			throw new \Error("Error: The temporary directory `{$this->tempDir}` is not writable.");
		}

		return $this;
	}

	public function getTempDir(): ?string
	{
		return $this->tempDir;
	}

	public function tempDirExists(): bool
	{
		return $this->getTempDir() !== null;
	}

	public function makeTempDir(int $permissions = 0777): self
	{
		mkdir($this->getTempDir(), $permissions, true);

		return $this;
	}

	public function removeTempDir(): void
	{

		$this->removeDirectory($this->getTempDir());
	}

	private function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		array_map(function ($file) use ($directory) {

			$path = $directory . DIRECTORY_SEPARATOR . $file;

			if (is_dir($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}, array_diff(scandir($directory), array('.', '..')));


		rmdir($directory);
	}
}
