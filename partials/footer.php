<style>
/* ensure sticky footer even if global stylesheet is absent */
footer.sticky {
    background: #343a40;
    color: white;
    text-align: center;
    padding: 15px 0;
    width: 100%;
    position: fixed;
    bottom: 0;
    left: 0;
    z-index: 1000;
}
body {
    padding-bottom: 60px; /* prevent overlap */
}
</style>
<footer class="bg-dark text-white text-center py-3 sticky">
    <p class="mb-0">&copy; <?php echo date("Y"); ?> AfyaBora Outpatient System. All Rights Reserved.</p>
</footer>
