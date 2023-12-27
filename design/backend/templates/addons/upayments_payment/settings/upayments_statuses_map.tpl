{if fn_allowed_for('ULTIMATE') && !$runtime.company_id || $runtime.simple_ultimate || fn_allowed_for('MULTIVENDOR')}
<div id="upayments_status_map_settings" class="in collapse">
    <div class="control-group">
        <strong class="control-label">{__('upayments_ipn_transaction_status')}</strong>
        <div class="controls">
            <strong style="float: left; padding-top: 5px;">{__('order_status')}</strong>
        </div>
    </div>
    {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
    <div class="control-group">
        <label class="control-label" for="elm_upayments_pending">{__("PENDING")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][PENDING]" id="elm_upayments_pending">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.PENDING) && $upayments_settings.upayments_statuses.PENDING == $k) || (!isset($upayments_settings.upayments_statuses.PENDING) && $k == 'O')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_upayments_completed">{__("COMPLETED")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][COMPLETED]" id="elm_upayments_completed">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.COMPLETED) && $upayments_settings.upayments_statuses.COMPLETED == $k) || (!isset($upayments_settings.upayments_statuses.COMPLETED) && $k == 'C')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_upayments_process">{__("PROCESS")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][PROCESS]" id="elm_upayments_process">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.PROCESS) && $upayments_settings.upayments_statuses.PROCESS == $k) || (!isset($upayments_settings.upayments_statuses.PROCESS) && $k == 'F')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_upayments_on_hold">{__("ON_HOLD")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][ON_HOLD]" id="elm_upayments_on_hold">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.ON_HOLD) && $upayments_settings.upayments_statuses.ON_HOLD == $k) || (!isset($upayments_settings.upayments_statuses.ON_HOLD) && $k == 'O')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_upayments_canceled">{__("CANCELED")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][CANCELED]" id="elm_upayments_canceled">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.CANCELED) && $upayments_settings.upayments_statuses.CANCELED == $k) || (!isset($upayments_settings.upayments_statuses.CANCELED) && $k == 'I')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_upayments_not_finished">{__("NOT_FINISHED")}:</label>
        <div class="controls">
            <select name="upayments_settings[upayments_statuses][NOT_FINISHED]" id="elm_upayments_not_finished">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($upayments_settings.upayments_statuses.NOT_FINISHED) && $upayments_settings.upayments_statuses.NOT_FINISHED == $k) || (!isset($upayments_settings.upayments_statuses.NOT_FINISHED) && $k == 'O')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>
{/if}