function buscarCliente() {
        var codigo = document.getElementById('codigo').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('codigo=' + codigo);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                document.getElementById('nombre').value = data.nombre;
                document.getElementById('apellido').value = data.apellido;
            }
        };
    }
