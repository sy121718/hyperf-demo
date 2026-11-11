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

class Create
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(EmailRequest $request): array
    {
        $templateCode = (string) $request->template_code;

        $this->templateModel->where('template_code', $templateCode)->exists()
            && paramError('模板编码已存在');

        $dbData = $request->toDbArrayFiltered();
        $dbData['template_type'] ??= 'html';
        $dbData['status'] ??= 1;

        $template = $this->templateModel->create($dbData);

        return $template->toArray();
    }
}
