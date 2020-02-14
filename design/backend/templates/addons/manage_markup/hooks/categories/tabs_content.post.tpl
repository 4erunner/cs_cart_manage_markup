{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}
{style src="lib/bootstrap_switch/stylesheets/bootstrapSwitch.css"}
{script src="js/addons/manage_markup/mmu_category_update.js"}

<div id="content_manage_markup">
    <input type="hidden" id="mmu_index" value="{$category_data.manage_markup|count}" />
    <input type="hidden" name="category_data[manage_markup_concurent_runtime]" value="{$category_data.manage_markup_concurent}" />

    {include file="common/subheader.tpl" title="{__("mmu_id")}" target="#manage_markup_categories_hook"}

    <div id="manage_markup_categories_hook" class="in collapse">
        <table class="table table-middle">
        <tbody>
            <tr>
                <td>{__("manage_markup.concurent_category")} <i class="cm-tooltip icon-question-sign" title="{__('manage_markup.tooltip.concurent_category')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="category_data[manage_markup_concurent]" value="1" {if $category_data.manage_markup_concurent == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        </table>
        <table class="table table-middle" id="manage_markup_rules">
        <thead class="cm-first-sibling">
        <tr>
            <th>{__("price_from")} </th>
            <th>{__("price_to")} </th>
            <th>{__("procent")} {include file="common/tooltip.tpl" tooltip=__("procent_about")}</th>
            <th>{__("margin")}{include file="common/tooltip.tpl" tooltip=__("margin_about")}</th>
            <th></th>
            <th></th>            
        </tr>
        </thead>
        <tbody>
        {foreach from=$category_data.manage_markup key=k item=v}
            <tr id="mmu-index-{$k}" class="mmu-id-{$v.id}" {if $v.status == "D" } style="background:#f5f5f5"{/if}>
                <td>
                    <input type="hidden" name="category_data[manage_markup][{$k}][id]" value="{$v.id}">
                    <input type="text" mmu-input="price-from" class="cm-value-decimal" name="category_data[manage_markup][{$k}][price_from]" value="{$v.price_from|default:"0"}" maxlength="13"></td>
                <td>
                    <input type="text" mmu-input="price-to" class="cm-value-decimal" name="category_data[manage_markup][{$k}][price_to]" value="{$v.price_to|default:"0"}" maxlength="13"></td>
                <td>
                    <input type="text" mmu-input="procent" class="cm-value-decimal" name="category_data[manage_markup][{$k}][procent]" value="{$v.procent|default:"0"}" maxlength="5"></td>
                <td>
                    <input type="text" mmu-input="margin" class="cm-value-decimal" name="category_data[manage_markup][{$k}][margin]" value="{$v.margin|default:"0"}" maxlength="13"></td>
                <td>
                    <a  mmu-index="{$k}" class="btn">X</a></td>
                <td>
                    {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$v.id status=$v.status hidden=true object_id_name="id" update_controller="manage_markup"  table="manage_markup" hidden=false}
                </td>
            </tr>
        {/foreach}
        </tbody>
        </table>
        <a class="btn" id="mmu_add_rule">{__("mmu_add_rule")}</a>
    </div>
</div>
