{* всплывающая форма подтверждения заказа *}
<form method="POST" action="cars/buy" id="buy_form" class="white-popup-block mfp-hide js-ajaxform"> 
  <legend>Подтвердите заказ</legend> 
  <p>После подтверждения заказа поставщиком, с вашего счета будет списана сумма <span id="buy_form_price"></span> <i class="fa fa-rub"></i>.
  Если на счету будет недостаточно средств, вам будет необходимо его пополнить.</p>
  <div class="form-group">
    <label for="buy_form_comment">{$_controller->lang['order.comment']}</label>
    <textarea class="form-control" name="comment" placeholder="{$_controller->lang['order.comment_desc']}"></textarea>
  </div>
  <input id="buy_form_id" name="id" type="hidden" value=""> 
  <button type="submit" class="btn btn-primary">Заказать</button> <button type="reset" class="btn btn-default">Отмена</button> 
</form>