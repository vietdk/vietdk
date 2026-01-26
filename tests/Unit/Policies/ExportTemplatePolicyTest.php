<?php

namespace Tests\Unit\Policies;

use App\Models\ExportTemplate;
use App\Models\User;
use App\Policies\ExportTemplatePolicy;
use Tests\TestCase;

class ExportTemplatePolicyTest extends TestCase
{
    public function test_editor_can_manage_export_templates(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $template = new ExportTemplate();

        $policy = new ExportTemplatePolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $template));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $template));
        $this->assertTrue($policy->delete($user, $template));
    }

    public function test_writer_cannot_manage_export_templates(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $template = new ExportTemplate();

        $policy = new ExportTemplatePolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $template));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $template));
        $this->assertFalse($policy->delete($user, $template));
    }
}
