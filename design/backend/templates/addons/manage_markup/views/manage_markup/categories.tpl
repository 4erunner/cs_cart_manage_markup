{script src="js/addons/manage_markup/mmu_category_update.js"}

{capture name="mainbox"}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}
<style>
span [class*="exicon-"]{ 
    padding: 0 10px 0 10px;
}
</style>
<div class="items-container">
    {if $categories_tree !== false}
        <table class="table table-tree table-middle">
            <thead>
            <tr>
                <th width="100%">
                {__("categories")}
                </th>
                <th width="14%" class="nowrap center">
                    {__("manage_markup.competitors")} {include file="common/tooltip.tpl" tooltip=__("manage_markup.tooltip.concurent_category")}
                </th>
                <th width="10%" class="nowrap right">
                </th>
            </tr>
            </thead>
        </table>
        <div style="width:100%; float: left">
        {include file="addons/manage_markup/views/manage_markup/components/category_tree.tpl"}
        </div>       
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
</div>
{/capture}

{include file="common/mainbox.tpl" title=__("mmu_id") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}