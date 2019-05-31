<?php

namespace Dimsav\Translatable;

use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class Locales
{
    /**
     * @var ConfigContract
     */
    protected $config;

    /**
     * @var TranslatorContract
     */
    protected $translator;

    public function __construct(ConfigContract $config, TranslatorContract $translator)
    {
        $this->config = $config;
        $this->translator = $translator;
    }
}
