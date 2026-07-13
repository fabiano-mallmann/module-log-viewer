<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Fsm_LogViewer',
    __DIR__
);
