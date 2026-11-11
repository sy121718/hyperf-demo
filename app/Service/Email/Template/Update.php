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

use App\Dto\Email\EmailRequest;
use App\Model\Notify\EmailTemplateModel;

class Update
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(EmailRequest $request): array
    {
        $id = (int) $request->id;
        $template = $this->templateModel->findOrNotFound($id, '邮件模板');

        if ($request->template_code !== null && $request->template_code !== $template->template_code) {
            $this->templateModel->where('template_code', $request->template_code)
                ->where('id', '!=', $id)
                ->exists() && paramError('模板编码已存在');
        }

        $template->update($request->toDbArrayFiltered());

        return $template->refresh()->toArray();
    }
}
