<template>
    <div class="ignore-status-email" data-toggle="tooltip" title="V prípade zasielania emailu pri zmene stavu bude tento email ignorovaný.">
        <label>
            Ignorovať notifikáciu
            <input type="checkbox" name="$ignore_status_email" value="1" :key="row.id">
        </label>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['row'],
}
</script>