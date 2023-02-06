<?php

/*
 * This file is a temporary patch for the PHP-CLI package Shell wrapper.
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