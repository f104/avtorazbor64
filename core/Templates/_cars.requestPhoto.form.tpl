{* всплывающая форма запроса фото *}
<form method="POST" action="item/requestphoto" id="requestphoto_form" class="white-popup-block mfp-hide js-ajaxform"> 
  <legend>Запрос фотографии</legend> 
  <div class="form-group">
    <label for="requestphoto_form_email">Ваш электронный адрес</label>
    <input type="email" id="requestphoto_form_email" name="email" class="form-control" value="{if $_core->isAuth}{$_core->authUser->email}{/if}" required>
    <div class="text-danger"></div>
  </div>
  <input id="requestphoto_form_id" name="id" type="hidden" value="{$item.id}"> 
  <button type="submit" class="btn btn-primary">Отправить</button> <button type="reset" class="btn btn-default">Отмена</button> 
</form>