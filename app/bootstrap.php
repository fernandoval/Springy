<?php

/**
 * Bootstrap application.
 *
 * @copyright 2022 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.0.1
 */

date_default_timezone_set('America/Sao_Paulo');

// Initialize all default dependencies, needed by framework and other controllers.
bindDefaultDependencies();

// An example function to start default template variables.
bindDefaultTemplateVars();
