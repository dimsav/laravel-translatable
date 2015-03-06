<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
                                         ->exclude('coverage')
                                         ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
                                ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
                               ->fixers(['short_array_syntax'])
                               ->finder($finder)
;