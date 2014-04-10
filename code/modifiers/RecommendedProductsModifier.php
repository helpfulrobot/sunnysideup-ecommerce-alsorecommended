<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_modifiers
 * @description: shows a list of recommended products
 * the product page / dataobject need to have a function RecommendedProductsForCart
 * which returns an array of IDs
 */
class RecommendedProductsModifier extends OrderModifier {

//--------------------------------------------------------------------  *** static variables

	private static $singular_name = "Recommended Product";
		function i18n_singular_name() { return _t("RecommendedProductsModifier.SINGULAR_NAME", "Recommended Product");}

	private static $plural_name = "Recommended Products";
		function i18n_plural_name() { return _t("RecommendedProductsModifier.PLURAL_NAME", "Recommended Products");}

//--------------------------------------------------------------------  *** static functions
	public function ShowForm() {
		return true;
	}

	static function get_form($controller) {
		return new RecommendedProductsModifier_Form($controller, 'RecommendedProducts');
	}

//-------------------------------------------------------------------- *** display functions
	function ShowInTable() {
		return false;
	}

	function CanRemove() {
		return false;
	}


// -------------------------------------------------------------------- *** table values
	function LiveCalculatedTotal() {
		return 0;
	}
	function LiveTableValue() {
		return 0;
	}

//-------------------------------------------------------------------- *** table titles
	function LiveName() {
		return "Recommended Products";
	}

	function Name() {
		if(!$this->canEdit()) {
			return $this->Name;
		}
		else {
			return $this->LiveName();
		}
	}

//-------------------------------------------------------------------- ***  database functions

	public function IsNoChange() {
		return true;
	}

}

class RecommendedProductsModifier_Form extends Form {

	private static $image_width = 100;

	private static $something_recommended_text = "Recommended Additions";

	private static $add_button_text = "Add Selected Items";

	private static $order_item_classname = "Product_OrderItem";

	function __construct($controller, $name) {
		$InCartIDArray = array();
		$recommendedProductsIDArray = array();
		$fieldsArray = array();
		if($items = ShoppingCart::get_items()) {
			foreach($items as $item) {
				$id = $item->Product()->ID;
				$InCartIDArray[$id] = $id;
			}
			foreach($items as $item) {
				//get recommended products
				if($item) {
					$product = $item->Product();
					if($product) {
						unset($recommendedProducts);
						$recommendedProducts = array();
						$recommendedProducts = $product->EcommerceRecommendedProducts();
						foreach($recommendedProducts as $recommendedProduct) {
							if(!in_array($recommendedProduct->ID, $InCartIDArray)) {
								$recommendedProductsIDArray[$recommendedProduct->ID] = $recommendedProduct->ID;
							}
						}
					}
				}
			}
		}
		if(count($recommendedProductsIDArray)) {
			Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
			//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
			//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
			Requirements::javascript("ecommerce_alsorecommended/javascript/RecommendedProductsModifier.js");
			Requirements::themedCSS("RecommendedProductsModifier", "ecommerce_alsrecommended");
			$fieldsArray[] = new HeaderField($this->config()->get("something_recommended_text"));
			foreach($recommendedProductsIDArray as $ID) {
				$product = Product::get()->byID($ID);
				//foreach product in cart get recommended products
				$imageID = $product->ImageID;
				$imagePart = '';
				if($product && $product->ImageID > 0) {
					$resizedImage = $product->Image()->SetWidth($this->Config()->get("image_width"));
					if(is_object($resizedImage) && $resizedImage) {
						$imageLink = $resizedImage->Filename;
						$imagePart = '<span class="secondPart"><img src="'.$imageLink.'" alt="'.Convert::raw2att($product->Title).'" /></span>';
					}
				}
				if(!$imagePart) {
					$imagePart = '<span class="secondPart noImage">[no image available for '.$product->Title.']</span>';
				}
				$priceAsMoney = EcommerceCurrency::get_money_object_from_order_currency($product->calculatedPrice());
				$pricePart = '<span class="firstPart">'.$priceAsMoney->Nice().'</span>';
				$title = '<a href="'.$product->Link().'">'.$product->Title.'</a>'.$pricePart.$imagePart.'';
				$newField = new CheckboxField($product->ID, $title);
				$fieldsArray[] = $newField;
			}
			$actions = new FieldList(new FormAction('processOrder', $this->config()->get("add_button_text")));
		}
		else {
			$actions = new FieldList();
		}
		$requiredFields = null;
		// 3) Put all the fields in one FieldSet
		$fields = new FieldList($fieldsArray);

		// 6) Form construction
		return parent::__construct($controller, $name, $fields, $actions, $requiredFields);
	}

	public function processOrder($data, $form) {
		$items = ShoppingCart::get_items();
		$URLSegments = array();
		foreach($data as $key => $value) {
			if(1 == $value) {
				$ids[$id] = inval($key);
			}
		}
		if(is_array($URLSegments) && count($URLSegments)) {
			$itemsToAdd = Product::get()
				->filter("ID", $ids);
			if($itemsToAdd->count()) {
				foreach($itemsToAdd as $item) {
					$order_item_classname = $this->config()->get("order_item_classname");
					ShoppingCart::add_new_item(new $order_item_classname($item));
				}
			}
		}
		if(Director::is_ajax()) {
			return $this->controller->renderWith("AjaxCheckoutCart");
		}
		else {
			$this->redirect(CheckoutPage::find_link());
		}
		return;
	}


//-------------------------------------------------------------------- *** debug
}
