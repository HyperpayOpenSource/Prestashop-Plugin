<?php

include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCard.php');

class HyperpaySavedCardsModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     *
     * @return void
     */
    public function initContent()
    {

        parent::initContent();

        // validate customer
        if (!Validate::isLoadedObject($this->context->customer))
            Tools::redirect('');

        $card_deleted = null;

        if (Tools::isSubmit('delete')) {
            $card = new HyperpayCard(Tools::getValue('id'));
            if (Validate::isLoadedObject($card) && $card->id_customer == $this->context->customer->id) {
                $card_deleted =  $card->delete() == 1 ? true : null;
            }
        }

        $this->context->smarty->assign([
            'cards' => HyperpayCard::getCustomerCards($this->context->customer->id),
            'card_deleted' => $card_deleted
        ]);


        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:hyperpay/views/templates/front/savedCards.tpl');
        } else {
            $this->setTemplate('savedCards16.tpl');
        }
    }
}