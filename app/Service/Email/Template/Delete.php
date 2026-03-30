<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Service\Email\Template;

use App\Model\Email\EmailTemplateModel;

class Delete
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $template = $this->templateModel->find($id);
        ! $template && paramError('邮件模板不存在');

        $template->delete();

        return true;
    }
}
