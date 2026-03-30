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

class Update
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $id > 0 || paramError('模板ID不能为空');

        $template = $this->templateModel->find($id);
        ! $template && paramError('邮件模板不存在');

        if (! empty($data['template_code']) && $data['template_code'] !== $template->template_code) {
            $this->templateModel->where('template_code', $data['template_code'])
                ->where('id', '!=', $id)
                ->exists() && paramError('模板编码已存在');
        }

        Db::beginTransaction();
        try {
            unset($data['id']);
            $template->update($data);
            Db::commit();
            return $template->fresh()->toArray();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
