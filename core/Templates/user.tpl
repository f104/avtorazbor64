{extends '_base.tpl'}

{block 'content'}
    {if $authUser}
        <section>
            <p>Рады видеть вас, {$authUser->name}!<br>
                Вы зарегистрированы в группе &laquo;{$authUser->getUserGroupName()}&raquo;.</p>

            {if $authUser->isBuyer()}
                {set $ordersWaitPaiment = $authUser->ordersWaitPaiment()}
                {set $rechargePaiment = 1000}
                {if $ordersWaitPaiment.total != 0}
                    <p class="text-danger"><strong>У вас {$ordersWaitPaiment.total|decl:'неоплаченный заказ,неоплаченных заказа,неоплаченных заказов':true} на&nbsp;сумму {$ordersWaitPaiment.cost}&nbsp;руб.</strong></p>
                    {if $ordersWaitPaiment.cost > $authUser->balance}
                        {set $rechargePaiment = $ordersWaitPaiment.cost - $authUser->balance}
                    {/if}
                {/if}
                <h3>Баланс покупателя: {$authUser->balance}&nbsp;<i class="fa fa-rub"></i></h3>
                <form method="get" action="fees/recharge" class="form-inline">
                    <div class="form-group">
                        <label for="OutSum">Сумма</label>
                        <input type="number" min="1" value="{$rechargePaiment}" name="OutSum" id="OutSum" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Пополнить</button>
                    <br><br>
                    <p>Вы будете перенаправлены на сайт платежной системы Robokassa.
                        {if $.const.PROJECT_MERCHANT_PERCENT?}
                            <br> 
                            Комиссия за перичисление средств составляет {$.const.PROJECT_MERCHANT_PERCENT}% от суммы платежа.
                        {/if}
                    </p>
                    <p class="text-danger">Внимание! Если вас просят оплатить заказ или пополнить баланс переводом на карту — 
                        не делайте этого, это мошенники.</p>
                </form>
            {/if}

            {$content}
            <br>
            <p>
                <a class="btn btn-primary btn-sm" href="user/profile">Редактировать профиль</a>  
                <a class="btn btn-danger btn-sm" href="user/logout">Выйти <i class="fa fa-sign-out fa-lg"></i></a>
            </p>
        </section>
    {else}
        <div class="row">
            <div class="col-sm-6">
                <!-- login -->
                {$formAuth}
                {$_controller->makeHauthLoginTpl()}
            </div>
            <div class="col-sm-6">
                <!-- register -->
                {$formRegister}
            </div>
        </div>
    {/if}
{/block}