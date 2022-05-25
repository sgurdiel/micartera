<?php
$output = `php bin/console doctrine:database:create --if-not-exists --quiet --env=test`;
$output .= `php bin/console doctrine:schema:update --force --quiet --env=test`;
$output .= `php bin/console doctrine:fixtures:load --quiet --env=test`;