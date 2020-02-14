<div id="content_manage_markup">
{if $runtime.controller == 'products' && $runtime.mode == 'update'}
    <label class="control-label">{__("manage_markup.recommended_price")}:</label>
    <input type="hidden" name="product_data[mm_force_list_price]" value="N" />
    <div class="controls">
        <label class="checkbox inline" for="elm_mm_force_list_price">
            <input type="checkbox" name="product_data[mm_force_list_price]" id="elm_mm_force_list_price" {if $product_data.mm_force_list_price == "Y"}checked="checked"{/if} value="Y" />
            {__("manage_markup.recommended_price_priority")}
        </label>
    </div>
{/if}
</div>
