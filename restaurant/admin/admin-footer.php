</main>

<script>
// Confirm all delete links
document.querySelectorAll('a[href*="delete="]').forEach(a => {
  a.addEventListener('click', e => {
    if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) {
      e.preventDefault();
    }
  });
});
</script>
</body>
</html>