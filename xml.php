<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$xml = new SimpleXMLElement('<yml_catalog />');
$xml->addAttribute('date', date("Y-m-d H:i:s"));
	$shop = $xml->addChild('shop');
		$shop->addChild('name', "ГеоЭксперт");
		$shop->addChild('company', "ГеоЭксперт");
		$shop->addChild('url', "https://geoexpert.ru/");
		$price = $shop->addChild('currencies');
		$rub = $price->addChild('currency');
		$rub->addAttribute('id', 'RUR');
		$rub->addAttribute('rate', 1);
		$categories = $shop->addChild('categories');
		$arSelect = Array("ID", "NAME", "DETAIL_TEXT", "DETAIL_PICTURE", "DETAIL_PAGE_URL", "IBLOCK_SECTION_ID");
		$arFilter = Array("IBLOCK_ID"=>24, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
		$items = $shop->addChild('offers');
		while($ob = $res->GetNextElement())
		{
			$arItem = $ob->GetFields();
			$item = $items->addChild('offer');
			$item->addAttribute('id', $arItem['ID']);
			$item->addAttribute('available', 'true');
			if(empty($arItem['DETAIL_PICTURE'])){
				$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru/bitrix/templates/aspro_max_custom/images/svg/no-photo.png';
			}else{
				$arItem['DETAIL_PICTURE'] = 'https://geoexpert.ru'.CFile::GetPath($arItem['DETAIL_PICTURE']);
			}
			$rsSection = \Bitrix\Iblock\SectionTable::getList(array(
				'filter' => array('IBLOCK_ID' => 24, 'ID' => $arItem['IBLOCK_SECTION_ID']), 
				'select' => array('NAME', 'ID'),
			));
			while ($arSection=$rsSection->fetch()) 
			{
				$arItem['IBLOCK_SECTION_ID'] = $arSection['NAME'];
				$section = $categories->addChild('category', $arItem['IBLOCK_SECTION_ID']);
				$section->addAttribute('id', $arSection['ID']);
				$sec_id = $arSection['ID'];
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
			if(empty($arItem['DETAIL_TEXT'])){
				$arItem['DETAIL_TEXT'] = 'Описание появится позже';
			}
			$name = $arItem['NAME'];
			$description = $arItem['DETAIL_TEXT'];
			$picture = $arItem['DETAIL_PICTURE'];
			$item->addChild('url', $arItem['DETAIL_PAGE_URL']);
			$item->addChild('price', $arItem['PRICE']);
			$item->addChild('currencyId', 'RUB');
			$item->addChild('categoryId', $sec_id);
			$item->addChild('picture', $picture);
			$item->addChild('name', preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $name));
			$item->addChild('description', htmlentities(strip_tags($description),ENT_XML1));
		}
Header('Content-type: text/xml');
print($xml->asXML());
?>
