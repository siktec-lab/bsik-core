<?php

/*
 * This file is a temporary patch for the PHP-CLI package Shell wrapper.
 */

/* TODO: env PATH is not passed to the shell command
 * it should be null to inherit the current environment.
 * https://stackoverflow.com/questions/9916766/php-proc-open-problems-on-windows
 */
/* TODO: add custom Descriptor to the shell command
 */
namespace Siktec\Bsik\Tools;

use Ahc\Cli\Helper\Shell as ShellBase;

class Shell extends ShellBase 
{
    /**
     * @param string $command Command to be executed
     * @param string $input   Input for stdin
     */
    public function __construct(protected string $command, protected ?string $input = null)
    {
        parent::__construct($command, $input);
    }

    public function loadCurrentEnvPath(): void
    {
        if (getenv('PATH') !== false) {
            $env = array('PATH' => getenv('PATH'));
            $this->setOptions(env : $env);
        }
    }

    public function setOptions(
        ?string $cwd = null,
        ?array $env = null,
        ?float $timeout = null,
        array $otherOptions = []
    ): self {
        $this->cwd            = $cwd;
        // $this->env            = $env ?? []; -> This is the bug - default env should be null to inherit the current environment
        $this->env            = $env;
        $this->processTimeout = $timeout;
        $this->otherOptions   = $otherOptions;

        return $this;
    }

    protected function getDescriptors(): array
    {
        $out = $this->isWindows() ? ['pipe', 'w'] : ['pipe', 'w'];

        return [
            self::STDIN_DESCRIPTOR_KEY  => ['pipe', 'r'],
            self::STDOUT_DESCRIPTOR_KEY => $out,
            self::STDERR_DESCRIPTOR_KEY => $out,
        ];
    }
}