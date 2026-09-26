<?php

namespace SimplyConnect\Laravel\Tests;

final class PanelDisabledTest extends TestCase
{
    public function test_panel_is_not_registered_by_default(): void
    {
        $this->get('/simply-connect')->assertNotFound();
    }
}
