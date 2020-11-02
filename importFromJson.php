<?php

use UmiCms\System\Auth\AuthenticationException;

require_once dirname(__FILE__) . '/standalone.php';

$auth = UmiCms\Service::Auth();

try {
    $auth->loginByEnvironment();
} catch (AuthenticationException $exception) {
    $buffer->clear();
    $buffer->status('401 Unauthorized');
    $buffer->setHeader('WWW-Authenticate', 'Basic realm="UMI.CMS"');
    $buffer->push('HTTP Authenticate failed');
    $buffer->end();
}

if (!permissionsCollection::getInstance()->isSv()) {
    echo '<html lang="ru"><head><meta charset="utf-8" /><title>1С-UMI Explorer — Unika</title></head>
        <body><p>Не хватает прав доступа</p>';
    exit;
}

$data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/products2.json'));

foreach ($data->items as $item) {
    $alt = $item->alt;
    $path = '/images/katalog/opravy/';
    $dir = $_SERVER['DOCUMENT_ROOT'] . $path;

    $image = '';
    foreach (scandir($dir) as $file) {
        if (stripos($file, $alt) !== false) {
            $image = $file;
            break;
        }
    }

    $name = $item->name;
    $price = $item->price;

    $hierarchy = umiHierarchy::getInstance();
    $hierarchyTypes = umiHierarchyTypesCollection::getInstance();
    $objectTypes = umiObjectTypesCollection::getInstance();
    $cmsController = cmsController::getInstance();

//родительский раздел
    $parent_id = 1568;
    $parentElement = $hierarchy->getElement($parent_id);
    $domain_id = $parentElement->getDomainId();
    $lang_id = $parentElement->getLangId();
    $hierarchy_type_id = $hierarchyTypes->getTypeByName("catalog", "object")->getId();

//идентификатор типа, отвечающего за товар
    $object_type_id = 80;

//Создать элемент с именем $data['name'], в разделе
    $element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $name, $name);

//получаем объект элемента
    $element = umiHierarchy::getInstance()->getElement($element_id, true);

// активируем и включаем показ элемента/товара
    $element->setIsActive(true);
    $element->setIsVisible(false);
    $element->setName($name);

//устанавливаем цену
    $element->setValue('price', $price);

    $element->setValue('h1', $name);
    $element->setValue('tip', $item->oprava);
    $element->setValue('pol', $item->gender);
    $element->setValue('brand', $item->brand);
    $element->setValue('material', $item->material);
    $element->setValue('forma', $item->form);
    $element->setValue('razmer', $item->size);
    $element->setValue('cvet_opravy', $item->color);
    $element->setValue('cvet_artikul', $item->colorVendor);

    if ($image) {
        $element->setValue('pic', '.' . $path . $image);
    }

//сохраняем изменения
    $element->getObject()->commit();
    $element->commit();
}
