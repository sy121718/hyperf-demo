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
use Hyperf\DbConnection\Db;
use Throwable;

class Create
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(array $data): array
    {
        empty($data['template_name']) && paramError('模板名称不能为空');
        empty($data['template_code']) && paramError('模板编码不能为空');

        $this->templateModel->where('template_code', $data['template_code'])->exists()
            && paramError('模板编码已存在');

        Db::beginTransaction();
        try {
            $template = $this->templateModel->create($data);
            Db::commit();
            return $template->fresh()->toArray();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
