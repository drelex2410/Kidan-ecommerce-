<script>
    function makeSlug(val) {
        const output = String(val || '').replace(/\s+/g, '-').toLowerCase();
        $('#slug').val(output);
    }
</script>
