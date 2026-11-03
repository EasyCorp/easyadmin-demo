<?php

namespace App\Admin\Action;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

final class SourceCodeAction
{
    public const REPOSITORY_URL = 'https://github.com/EasyCorp/easyadmin-demo/blob/main/';

    public static function create(): Action
    {
        return Action::new('viewSource', 'action.view_source', 'code')
            ->linkToUrl('#source-code-modal')
            ->setHtmlAttributes(['data-bs-toggle' => 'modal'])
            ->asTextLink()
            ->createAsGlobalAction();
    }
}
