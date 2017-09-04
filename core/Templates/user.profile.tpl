{extends '_base.tpl'}

{block 'content'}
  <!-- user profile -->
    <form id="user_profile_form" method="post">

      <fieldset>
        <legend>Ваши регистрационные данные</legend>

        <div class="form-group required {$errors['name'] ? 'has-error' : ''}">
          <label for="user_profile_form_name">Имя</label>
          <input type="text" name="name" class="form-control" id="user_profile_form_name" required value="{$fields['name']|e}">
          <div class="text-danger">{$errors['name']}</div>
        </div>

        <div class="form-group">
          <label>Ваш текущий e-mail</label>
          <input type="text" name="email" class="form-control" readonly value="{$fields['email']|e}">
          <div class="text-danger">{$errors['email']}</div>
        </div>
        
        <div class="form-group {$errors['newemail'] ? 'has-error' : ''}">
          <label for="user_profile_form_email">Новый e-mail</label>
          <input type="email" name="newemail" class="form-control" id="user_profile_form_email" value="{$fields['newemail']|e}">
          <div class="text-danger">{$errors['newemail']}</div>
          <p class="help-block">Внимание! Смена электронного адреса приведет к выходу из системы и необходимости подтвердить новый e-mail!</p>
        </div>
        
        <div class="form-group has-feedback {$errors['password'] ? 'has-error' : ''}">
          <label for="user_profile_form_password">Новый пароль</label>
          <div class="password-view-wrapper eye-closed">
            <i class="fa fa-eye-slash" title="Показать пароль"></i>
            <input type="password" name="newPassword" class="form-control" id="user_profile_form_password">
          </div>
          <div class="text-danger">{$errors['password']}</div>
          <p class="help-block">Оставьте пустым, если не хотите менять пароль.</p>
        </div>
        
        <div class="form-group required {$errors['region_id'] ? 'has-error' : ''}">
          <label for="user_profile_form_region_id">Регион</label>
          <select name="region_id" class="form-control" id="user_profile_form_region_id" required>
            {foreach $regions as $k => $v}
              <option value="{$k}" {if $fields['region_id'] == $k}selected{/if}>{$v}</option>
            {/foreach}
          </select>
          <div class="text-danger">{$errors['region_id']}</div>
        </div>
        <div class="form-group required {$errors['city'] ? 'has-error' : ''}">
          <label for="user_profile_form_city">Город</label>
          <input type="text" name="city" class="form-control js-typeahead-city" id="user_profile_form_city" value="{$fields['city']|e}" required>
          <div class="text-danger">{$errors['city']}</div>
        </div>
        <div class="form-group required {$errors['phone'] ? 'has-error' : ''}">
          <label for="user_profile_form_phone">Телефон</label>
          <input type="text" name="phone" class="form-control" id="user_profile_form_phone" required value="{$fields['phone']|e}" maxlength="50">
          <div class="text-danger">{$errors['phone']}</div>
        </div>
        
        <input type="submit" name="update_profile" class="btn btn-primary" value="Сохранить"> <a href="user" class="btn btn-default">Отменить</a>

      </fieldset>
        
      {$hauth}

    </form>
  <!-- user profile -->
{/block}