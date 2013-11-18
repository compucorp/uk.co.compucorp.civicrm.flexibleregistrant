
<div class="content bold" id="skipped_participant">
    {ts}Skipped Participants{/ts}:
    {$skip_count}
</div>
<div class="content bold" id="total_participant">
    {ts}Total Participants{/ts}:
    {$total_count}
</div>

<script type="text/javascript">
{literal} 
    cj(document).ready(function($) {
        //Add Skipped Participants
        var eventTotalNode = $('div.content.bold:contains("Event Total")');
        $('#skipped_participant').insertAfter(eventTotalNode);
        
        //Add Total Participants
        var participantTotal = $('div.content.bold:contains("Total Participants")');
        participantTotal.replaceWith($('#total_participant'));
        
        //Remove "Total participants row" in each table 
        var totalHeaders = $('div.crm-group.event_fees-group th.right:last-child');
        var totalParticipants = $('div.crm-group.event_fees-group td.right:last-child');
        totalHeaders.remove();
        totalParticipants.remove();
        
    });
{/literal} 
</script>
