<div class="container">
    <div class="row">
        <% if $FilterMap.Count %>
            <div class="col-md-3">
                <div class="filter-list">
                    <h2 class="norican">Filters</h2>
                    <% include Vulcan\Search\Pages\FilterList %>
                    <div>
                        <button type="button" class="btn btn-primary" id="applyFilters">Apply</button>
                    </div>
                </div>
            </div>
        <% end_if %>
        <div class="col-md-<% if $FilterMap.Count %>9<% else %>12<% end_if %>">
            <div class="row">
                <div class="col-md-8">
                    <div id="Search">
                        Search:
                        <input type="text" name="q" placeholder="Search..."<% if $SearchTerm %> value="$SearchTerm"<% end_if %>/>
                    </div>
                </div>
                <% if $Filters %>
                <div class="col-md-4">
                    <p class="text-right">Sort by <select class="text-left" id="searchSort">
                        <% loop $Filters %>
                            <option value="$Title"<% if $Top.SortValue == $Title %> selected<% end_if %>>$Title</option>
                        <% end_loop %>
                    </select></p>
                </div>
                <% end_if %>
            </div>

            <% if $SearchResults %>
                <div class="results">
                    <% loop $SearchResults %>
                        <% with $Record %>
                            $SearchRender
                        <% end_with %>
                    <% end_loop %>
                </div>
                <div class="text-center">
                    <div class="unique-pagination">
                        <% if $SearchResults.NotFirstPage %>
                            <a class="prev" href="$SearchResults.PrevLink" title="View the previous page"><i class="fa fa-chevron-circle-left"></i><span> Prev</span></a>
                        <% else %>
                            <a class="prev disabled"><i class="fa fa-chevron-circle-left"></i><span> Prev</span></a></li>
                        <% end_if %>
                        <ul class="pagination">
                            <% loop $SearchResults.PaginationSummary %>
                                <% if $CurrentBool %>
                                    <li class="active"><a href="$Link" title="View page $PageNum">$PageNum</a></li>
                                <% else %>
                                    <% if $Link %>
                                        <li><a href="$Link" title="View page $PageNum">$PageNum</a></li>
                                    <% else %>
                                        <li class="disabled"><a>...</a></li>
                                    <% end_if %>
                                <% end_if %>
                            <% end_loop %>
                        </ul>
                        <% if $SearchResults.NotLastPage %>
                            <a class="next" href="$SearchResults.NextLink" title="View the next page"><span>Next </span><i class="fa fa-chevron-circle-right"></i></a>
                        <% else %>
                            <a class="disabled next"><span>Next </span><i class="fa fa-chevron-circle-right"></i></a>
                        <% end_if %>
                    </div>
                </div>
            <% else %>
                <div class="message">No results found</div>
            <% end_if %>
        </div>
    </div>
</div>