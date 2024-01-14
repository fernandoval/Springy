<?php

/*
 * Bootstrap application.
 */

date_default_timezone_set('America/Sao_Paulo');

// Initialize all default dependencies, needed by framework and other controllers.
bindDefaultDependencies();

// An example function to start default template variables.
bindDefaultTemplateVars();
