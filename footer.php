</main>
<script>
// Auto refresh stock page every 30s
if (new URLSearchParams(window.location.search).get('page') === 'dashboard' 
    || !new URLSearchParams(window.location.search).get('page')) {
    setInterval(() => location.reload(), 30000);
}
</script>
</body>
</html>
