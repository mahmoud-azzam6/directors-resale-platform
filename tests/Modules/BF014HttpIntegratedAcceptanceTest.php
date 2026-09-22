<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);$routes=(string)file_get_contents($root.'/routes/api.php');
foreach(['/property-categories','/unit-types','/measurement-definitions','/attribute-definitions','/geographic-locations','/developers','/projects','/project-phases','/configurations/{configurationId}/activate']as$route)if(!str_contains($routes,$route))throw new RuntimeException("Missing route $route");
foreach(['seed','assignments','proposals','listings','marketplace']as$forbidden)if(str_contains(strtolower($routes),$forbidden))throw new RuntimeException("Unexpected route surface $forbidden");
foreach(glob($root.'/app/Modules/Property/Controllers/*Controller.php')?:[]as$file){$source=(string)file_get_contents($file);if(str_contains($source,'Repository')||str_contains($source,'->transaction(')||str_contains($source,'ForUpdate')||str_contains($source,'SELECT '))throw new RuntimeException('Controller boundary: '.basename($file));}
foreach(['BF014CatalogReadHttpTest.php','BF014CatalogMutationHttpTest.php','BF014ConfigurationC1HttpTest.php','BF014ConfigurationC2HttpTest.php','BF014ConfigurationC3HttpTest.php','BF014GeographyHttpTest.php','BF014DevelopmentHttpTest.php','BF014GeographyDevelopmentHttpIntegratedTest.php','BF014PermissionFoundationTest.php']as$test){passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/'.$test),$status);if($status!==0)throw new RuntimeException("Failed $test");}
echo "BF014 HTTP integrated acceptance: PASS\n";
