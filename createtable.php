<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
?>
<?
$json = file_get_contents('php://input');
$fields = json_decode($json, true);
$query = $fields;
use Bitrix\Main;
use Bitrix\Main\Loader;
Loader::includeModule("iblock");
if($_REQUEST['yandex'] == 1){
	//Получаю все элементы инфоблока 24 
	$dbItem = \Bitrix\Iblock\ElementTable::getList(array(
		'select' => array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_TEXT', 'DETAIL_PICTURE', 'IBLOCK_SECTION_ID'),
		'filter' => array('IBLOCK_ID' => 24),
		'order' => array('TIMESTAMP_X' => 'ASC'),
	));
	while ($arItem = $dbItem->fetch()) {
		//Ставлю заглушку если нет изображения
		if(empty($arItem['DETAIL_PICTURE'])){
			$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru/bitrix/templates/aspro_max_custom/images/svg/no-photo.png';
		}else{
			$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru'.CFile::GetPath($arItem['DETAIL_PICTURE']);
		}
		//Получаю разделы элементов
		$rsSection = \Bitrix\Iblock\SectionTable::getList(array(
			'filter' => array('IBLOCK_ID' => 24, 'ID' => $arItem['IBLOCK_SECTION_ID']), 
			'select' => array('NAME'),
		));
		while ($arSection=$rsSection->fetch()) 
		{
			$arItem['IBLOCK_SECTION_ID'] = $arSection['NAME'];
		}
		//Получаю цену
		$rsPrice = \Bitrix\Catalog\PriceTable::getList(array(
			'filter' => array('CATALOG_GROUP.XML_ID'=>'f9ad6cfc-94fb-11ea-abd1-c809a87226cf','PRODUCT_ID'=>$arItem['ID']),
			'select' => array('PRICE'),
		));
		while ($arPrice=$rsPrice->fetch()) 
		{
			$arItem['PRICE'] = $arPrice['PRICE'];
		}
		//Получаю свойство хит для выовда в таблицу
		$dbProperty = \CIBlockElement::getProperty($arItem['IBLOCK_ID'], $arItem['ID'], array("sort", "asc"), array('CODE' => 'HIT'));
		while ($arProperty = $dbProperty->GetNext()) {
			if ($arProperty['VALUE']) {
				$arItem['HIT'] = 'Да';
			}else{
				$arItem['HIT'] = '';
			}
		}
		//Есть элементы без раздела
		if(empty($arItem['IBLOCK_SECTION_ID'])){
			$arItem['IBLOCK_SECTION_ID'] = 'Без категории';
		}
		//У Яндекса ограничение на описание 250 символов
		if(mb_strlen($arItem['DETAIL_TEXT'],'UTF-8') > 230){
			$arItem['DETAIL_TEXT'] = mb_substr($arItem['DETAIL_TEXT'], 0, 230, 'UTF-8').'...';
		}
		//У Яндекса ограничение на название 200 символов
		if(mb_strlen($arItem['NAME'],'UTF-8') > 190){
			$arItem['NAME'] = mb_substr($arItem['NAME'], 0, 190, 'UTF-8').'...';
		}
		$rows[] = array($arItem['IBLOCK_SECTION_ID'], $arItem['NAME'], $arItem['DETAIL_TEXT'], $arItem['PRICE'], $arItem['DETAIL_PICTURE'], $arItem['HIT'], '');
	}
	//Формирую колонки
	$header = array(
		'Категория'=>'string',
		'Название'=>'string',
		'Описание'=>'string',
		'Цена'=>'string',
		'Фото'=>'string',
		'Популярный товар'=>'string',
		'В наличии'=>'string',
	);
	$writer = new XLSXWriter();
	
	$writer->writeSheetHeader('Sheet1', $header);
	//Записываю в xlsx
	foreach($rows as $row)
		$writer->writeSheetRow('Sheet1', $row);
	//Задаю название	
	$file = 'yandex_spravochnik.xlsx';
	$writer->writeToFile($file);

	if (file_exists($file)) {
		if (ob_get_level()) { ob_end_clean(); }
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		//Скачиваю файл 
		readfile($file);
		//Удаляю файл 
		unlink($file);
		exit;
	}
}
//Все тоже самое,но получаю элементы через старое ядро
if($_REQUEST['2gis'] == 1){
	$arSelect = Array("ID", "NAME", "DETAIL_TEXT", "DETAIL_PICTURE", "DETAIL_PAGE_URL");
	$arFilter = Array("IBLOCK_ID"=>24, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
	while($ob = $res->GetNextElement())
	{
		$arItem = $ob->GetFields();
		if(empty($arItem['DETAIL_PICTURE'])){
			$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru/bitrix/templates/aspro_max_custom/images/svg/no-photo.png';
		}else{
			$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru'.CFile::GetPath($arItem['DETAIL_PICTURE']);
		}
		$arItem['DETAIL_PAGE_URL'] = 'https://geoexpert.ru'.$arItem['DETAIL_PAGE_URL'];
		$rsPrice = \Bitrix\Catalog\PriceTable::getList(array(
			'filter' => array('CATALOG_GROUP.XML_ID'=>'f9ad6cfc-94fb-11ea-abd1-c809a87226cf','PRODUCT_ID'=>$arItem['ID']),
			'select' => array('PRICE'),
		));
		while ($arPrice=$rsPrice->fetch()) 
		{
			$arItem['PRICE'] = $arPrice['PRICE'];
		}
		$rows[] = array($arItem['NAME'], $arItem['PRICE'], $arItem['DETAIL_PAGE_URL'], $arItem['DETAIL_PICTURE'], $arItem['DETAIL_TEXT']);
	}
	$header = array(
		'Наименование товара'=>'string',
		'Цена'=>'string',
		'Ссылка на товар на сайте магазина'=>'string',
		'Ссылка на картинку'=>'string',
		'Описание'=>'string'
	);
	$writer = new XLSXWriter();
	
	$writer->writeSheetHeader('Sheet1', $header);
	foreach($rows as $row)
		$writer->writeSheetRow('Sheet1', $row);
	
	$file = '2gis_spravochnik.xlsx';
	$writer->writeToFile($file);

	if (file_exists($file)) {
		if (ob_get_level()) { ob_end_clean(); }
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile($file);
		unlink($file);
		exit;
	}
}
?>
