$(document).ready(function () {
    $("input[name=inn]").suggestions({
        serviceUrl: "https://suggestions.dadata.ru/suggestions/api/4_1/rs",
        token: "aba02aaf4153eb411ce664d460dff6e8f4711067",
        type: "PARTY",
        count: 5,
        minChars: 3,
        formatSelected: function(suggestion) {
          return suggestion.data.inn;
        },
        /* Вызывается, когда пользователь выбирает одну из подсказок */
        onSelect: function(suggestion) {
             console.log(suggestion);
            // this привязано к input-элементу
            var $form = $(this).parents('form');
            $form.find('[name=kpp]').val(suggestion.data.kpp);
            $form.find('[name=company_name]').val(suggestion.value);
            $form.find('[name=legal_address]').val(suggestion.data.address.value);
            $form.find('[name=actual_address]').val(suggestion.data.address.value);
            $form.find('[name=ogrn]').val(suggestion.data.ogrn);
            $form.find('[name=okved]').val(suggestion.data.okved);
            $form.find('[name=okpo]').val(suggestion.data.okpo);
            if (suggestion.data.address.data) {
              $form.find('[name=index]').val(suggestion.data.address.data.postal_code);
            }
            if (suggestion.data.management) {
              $form.find('[name=dir_name]').val(suggestion.data.management.name);
            }
        }
    });
    $("input[name=bik]").suggestions({
        serviceUrl: "https://suggestions.dadata.ru/suggestions/api/4_1/rs",
        token: "aba02aaf4153eb411ce664d460dff6e8f4711067",
        type: "BANK",
        count: 5,
        minChars: 3,
        formatSelected: function(suggestion) {
          return suggestion.data.bic;
        },
        /* Вызывается, когда пользователь выбирает одну из подсказок */
        onSelect: function(suggestion) {
            //console.log(suggestion);
            // this привязано к input-элементу
            var $form = $(this).parents('form');
            $form.find('[name=bank_name]').val(suggestion.data.name.payment);
            $form.find('[name=k_schet]').val(suggestion.data.correspondent_account);
        }
    });
});