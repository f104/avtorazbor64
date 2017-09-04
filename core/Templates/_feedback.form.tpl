{*
  klfjgdasdfjhweiufs => name
  poiwerlkjsdfnmvxzsdf => email
  ouikjhsmnnbvytuoertkjh => phone
  jklashfafouiyqwerkhjg => text
*}
<form id="feedback_form" action="feedback" method="post" name="feedback" class="js-ajaxform">

  <fieldset>
    <legend>Написать письмо</legend>

    <div class="form-group {$errors['name'] ? 'has-error' : ''}">
      <label for="feedback_form_name">Представьтесь, пожалуйста</label>
      <input type="text" name="klfjgdasdfjhweiufs" class="form-control" id="feedback_form_name" required="" maxlength="255" value="{$fields['name']|e}">
      <div class="text-danger">{$errors['name']}</div>
    </div>

    <div class="form-group {$errors['name'] ? 'has-error' : ''}">
      <label for="feedback_form_email">Ваш e-mail</label>
      <input type="email" name="poiwerlkjsdfnmvxzsdf" class="form-control" id="feedback_form_email" required="" value="{$fields['email']|e}">
      <div class="text-danger">{$errors['email']}</div>
    </div>

    <div class="form-group {$errors['phone'] ? 'has-error' : ''}">
      <label for="call_form_phone">Ваш телефон</label>
      <input type="text" name="ouikjhsmnnbvytuoertkjh" class="form-control" id="feedback_form_phone" maxlength="255" value="{$fields['phone']|e}" placeholder="Не забудьте указать код города.">
      <div class="text-danger">{$errors['phone']}</div>
    </div>

    <div class="form-group {$errors['text'] ? 'has-error' : ''}">
      <label for="feedback_form_text">Какая информация вас интересует?</label>
      <textarea name="jklashfafouiyqwerkhjg" class="form-control" rows="7" id="feedback_form_text" required="">{$fields['text']}</textarea>
      <div class="text-danger">{$errors['text']}</div>
    </div>

    <button type="submit" class="btn btn-primary js-submit">Отправить</button> <button type="reset" class="btn btn-default">Отменить</button>

  </fieldset>

</form>