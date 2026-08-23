document.addEventListener("DOMContentLoaded", () => {
    // Upload
    const fileInput = document.getElementById('upload-file-input');
    const progress = document.getElementById('upload-progressbar');
    const statusEl = document.getElementById('upload-status');
    const selectFileButton = document.getElementById('select-file-button');
    const fileNameInput = document.getElementById('upload-file-name');

    // General
    const overlay = document.getElementById("overlay");
    const createFolderDiv = document.getElementById("create-folder");
    const renameFolderDiv = document.getElementById("rename-folder");
    const updateFolderMenu = document.getElementById("update-folder-menu");
    const confirmMenu = document.getElementById("confirm-menu");
    const uploadMenu = document.getElementById("upload-file");

    const createButton = document.getElementById("create");
    const createFolderButton = document.getElementById("create-folder-button");
    const renameFolderButton = document.getElementById("rename-folder-button");
    const uploadButton = document.getElementById("upload");
    const uploadFileButton = document.getElementById("upload-file-button");

    const pinButton = document.getElementById("pin");
    const unpinButton = document.getElementById("unpin");
    const markFavoriteButton = document.getElementById("mark-favorite");
    const unmarkFavoriteButton = document.getElementById("unmark-favorite");
    const renameButton = document.getElementById("rename");
    const deleteButton = document.getElementById("delete");
    const confirmButton = document.getElementById("confirm-button");
    const cancelButton = document.getElementById("cancel-button");

    const filesContainer = document.getElementById("files");
    const fileTemplate = document.getElementById("file-template");

    const searchBar = document.getElementById("search");

    const gridButton = document.getElementById("grid-mode");
    const listButton = document.getElementById("list-mode");

    const root = document.getElementById("metadata-root").value;

    let activeMenu;
    let menuButtons;
    let activeFolder;

    function hideOverlay() {
        overlay.classList.add("hide");
    }
    function showOverlay() {
        overlay.classList.remove("hide");
    }

    function hideCreateFolder() {
        hideOverlay();
        createFolderDiv.classList.add("hide");
        const tmp = createFolderDiv.querySelector('input[type=text]');
        tmp.value = "";
    }
    function showCreateFolder() {
        showOverlay();
        createFolderDiv.classList.remove("hide");
    }

    function hideRenameFolder() {
        hideOverlay();
        renameFolderDiv.classList.add("hide");
        const tmp = renameFolderDiv.querySelector('input[type=text]');
        tmp.value = "";
    }
    function showRenameFolder() {
        showOverlay();
        renameFolderDiv.classList.remove("hide");
    }

    function hideConfirmMenu() {
        hideOverlay();
        if (!confirmMenu.classList.contains("hide")) {
            confirmMenu.classList.add("hide");
        }
    }
    function showConfirmMenu() {
        showOverlay();
        if (confirmMenu.classList.contains("hide")) {
            confirmMenu.classList.remove("hide");
        }
    }

    function hideUpdateFolderMenu() {
        hideOverlay();
        if (!updateFolderMenu.classList.contains("hide")) {
            updateFolderMenu.classList.add("hide");
        }
        overlay.style.backgroundColor = "rgba(0, 0, 0, 0.25)";
    }
    function showUpdateFolderMenu() {
        showOverlay();
        if (updateFolderMenu.classList.contains("hide")) {
            updateFolderMenu.classList.remove("hide");
            // Pinned
            if (activeFolder.type === "folder") {
                if (activeFolder.pinned) {
                    if (!pinButton.classList.contains("hide")) {
                        pinButton.classList.add("hide");
                    }
                    if (unpinButton.classList.contains("hide")) {
                        unpinButton.classList.remove("hide");
                    }
                } else {
                    if (pinButton.classList.contains("hide")) {
                        pinButton.classList.remove("hide");
                    }
                    if (!unpinButton.classList.contains("hide")) {
                        unpinButton.classList.add("hide");
                    }
                }
            } else {
                if (!pinButton.classList.contains("hide")) {
                    pinButton.classList.add("hide");
                }
                if (!unpinButton.classList.contains("hide")) {
                    unpinButton.classList.add("hide");
                }
            }

            // Favorite
            if (activeFolder.favorite) {
                if (!markFavoriteButton.classList.contains("hide")) {
                    markFavoriteButton.classList.add("hide");
                }
                if (unmarkFavoriteButton.classList.contains("hide")) {
                    unmarkFavoriteButton.classList.remove("hide");
                }
            } else {
                if (markFavoriteButton.classList.contains("hide")) {
                    markFavoriteButton.classList.remove("hide");
                }
                if (!unmarkFavoriteButton.classList.contains("hide")) {
                    unmarkFavoriteButton.classList.add("hide");
                }
            }
        }
    }

    function createFolder(parent, name, done) {
        fetch(
            '/api/folder/create', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    parent: parent,
                    name: name,
                })
            }
        ).then(r => {if (r.ok) {done()}});
    }

    function refresh() {
        window.location.reload();
    }

    function addItem(item) {
        if (item.type === "folder") {
            addFolder(item);
        } else if (item.type === "file") {
            const file = fileTemplate.cloneNode(true);
            file.removeAttribute("id");
            file.querySelector('a').textContent = item.name;
            file.querySelector('a').href = '/folder/' + item.uuid;
            file.querySelector(`img[data-type="${item.mimeType}"]`).classList.remove('hide');
            file.dataset.uuid = item.uuid;
            file.dataset.name = item.name;
            file.dataset.favorite = item.favorite;
            file.dataset.type = "file";
            file.dataset.mimeType = item.type;
            filesContainer.appendChild(file);
        }
    }

    function addFolder(folder) {
        const file = fileTemplate.cloneNode(true);
        file.removeAttribute("id");
        file.querySelector('a').textContent = folder.name;
        file.querySelector('a').href = '/folder/' + folder.uuid;
        file.querySelector('img[data-type="folder"]').classList.remove('hide');
        file.dataset.uuid = folder.uuid;
        file.dataset.name = folder.name;
        file.dataset.pinned = folder.pinned;
        file.dataset.favorite = folder.favorite;
        file.dataset.type = "folder";
        filesContainer.appendChild(file);
    }

    function getFolder(parent) {
        fetch('/api/folder/get', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                parent: parent,
            })
        }).then( async (res) => {
            if (res.ok) {
                const data = await res.json();

                data.forEach(item => {
                    addItem(item);
                })
                updateMenuButtonsList();
            }
        });
    }

    function clearFiles() {
        filesContainer.querySelectorAll('.file:not(#file-template)').forEach(file => {
            filesContainer.removeChild(file);
        })
    }

    function updateMenuButtonsList() {
        menuButtons = document.querySelectorAll(".file img.menu");
        menuButtons.forEach(menuButton => {
            function onClick(e) {
                const el = e.currentTarget;
                activeFolder = {
                    'uuid': el.parentElement.dataset.uuid,
                    'name': el.parentElement.dataset.name,
                    'pinned': el.parentElement.dataset.pinned?.trim().toLowerCase() === "true",
                    'favorite': el.parentElement.dataset.favorite?.trim().toLowerCase() === "true",
                    'type': el.parentElement.dataset.type,
                    'mimeType': el.parentElement.dataset.mimeType,
                };

                overlay.style.backgroundColor = "rgba(0, 0, 0, 0.0)";
                activeMenu = {
                    "name": "Update Folder",
                    "hide": hideUpdateFolderMenu,
                    "show": showUpdateFolderMenu
                };
                activeMenu.show();

                const rect = el.getBoundingClientRect();
                const menuRect = updateFolderMenu.getBoundingClientRect();

                renameFolderDiv.querySelector('input[type=text]').value = el.parentElement.querySelector('a').textContent;

                updateFolderMenu.style.top = rect.y.toString() + 'px';
                updateFolderMenu.style.left = (rect.x - menuRect.width).toString() + 'px';
            }

            menuButton.removeEventListener("click", onClick);
            menuButton.addEventListener("click", onClick)
        })
    }

    function deleteFolder(uuid) {
        fetch('/api/folder/delete', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                uuid: uuid,
            })
        }).then((r) => {
            activeMenu.hide();
            activeMenu = {};
        })
    }

    function renameFolder(folder) {
        fetch('/api/folder/rename', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                uuid: folder.uuid,
                name: folder.name,
            })
        }).then((r) => {
            if (r.ok) {
                activeMenu.hide();
                activeMenu = {};

                clearFiles();
                getFolder(root);
            }
        })
    }

    function setPin(folder, pinned) {
        fetch('/api/folder/pin', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                uuid: folder.uuid,
                pinned: pinned,
            })
        }).then((res) => {
            if (res.ok) {
                refresh();
            }
        });
    }

    function setFavorite(item, favorite) {
        fetch('/api/item/favorite', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                uuid: item.uuid,
                itemType: item.type,
                favorite: favorite,
            })
        }).then((res) => {
            if (res.ok) {
                activeMenu.hide();
                activeMenu = {};

                clearFiles();
                getFolder(root);
            }
        });
    }

    function showUploadMenu() {
        showOverlay();
        if (uploadMenu.classList.contains('hide')) {
            uploadMenu.classList.remove('hide');
        }
    }
    function hideUploadMenu() {
        hideOverlay();
        if (!uploadMenu.classList.contains('hide')) {
            uploadMenu.classList.add('hide');
        }
        selectFileButton.value = '';
        fileNameInput.value = '';
        statusEl.textContent = '';
        progress.value = 0;

        if (!statusEl.classList.contains('hide')) {
            statusEl.classList.add('hide');
        }
        if (!progress.classList.contains('hide')) {
            progress.classList.add('hide');
        }
    }

    overlay.addEventListener("click", () => {
        hideOverlay();
        if (activeMenu) {
            activeMenu.hide()
            activeMenu = {};
        }
    });

    createButton.addEventListener("click", () => {
        showCreateFolder();
        activeMenu = {
            "name": "Create Folder",
            "hide": hideCreateFolder,
            "show": showCreateFolder
        };
    });
    createFolderButton.addEventListener("click", () => {
        createFolder(root, createFolderDiv.querySelector('input[type=text]').value, () => {
            clearFiles();
            getFolder(root)
        });
        hideCreateFolder();
        activeMenu = {};
    });

    searchBar.addEventListener("input", () => {
        fetch('/api/folder/search', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                search: searchBar.value.toLowerCase(),
                parent: root,
            })
        }).then( async (res) => {
            if (res.ok) {
                const data = await res.json();
                clearFiles();

                data.forEach(item => {
                    addItem(item);
                })
                updateMenuButtonsList();
            }
        })
    });

    gridButton.addEventListener("click", () => {
        if (!gridButton.classList.contains('selected')) {
            gridButton.classList.add("selected");
            listButton.classList.remove("selected");

            filesContainer.classList.remove("list");
            filesContainer.classList.add("grid");
        }
    })
    listButton.addEventListener("click", () => {
        if (!listButton.classList.contains('selected')) {
            listButton.classList.add("selected");
            gridButton.classList.remove("selected");

            filesContainer.classList.remove("grid");
            filesContainer.classList.add("list");
        }
    })

    deleteButton.addEventListener("click", () => {
        hideUpdateFolderMenu();

        activeMenu = {
            "name": "Confirm Menu",
            "hide": hideConfirmMenu,
            "show": showConfirmMenu
        }
        showConfirmMenu();
    })

    cancelButton.addEventListener("click", () => {
        hideConfirmMenu();
        activeMenu = {}
    });

    confirmButton.addEventListener("click", () => {
        deleteFolder(activeFolder.uuid);
        clearFiles();
        getFolder(root);
    })

    renameButton.addEventListener("click", () => {
        activeMenu.hide();
        activeMenu = {
            "name": "Rename Folder",
            "hide": hideRenameFolder,
            "show": showRenameFolder
        }
        activeMenu.show();
    });

    renameFolderButton.addEventListener("click", () => {
        var input = renameFolderDiv.querySelector('input[type=text]');
        renameFolder({
            name: input.value,
            uuid: activeFolder.uuid,
        })
        input.value = "";
    });

    pinButton.addEventListener("click", () => {
        setPin(activeFolder, true);
    });
    unpinButton.addEventListener("click", () => {
        setPin(activeFolder, false);
    });

    markFavoriteButton.addEventListener("click", () => {
        setFavorite(activeFolder, true);
    });
    unmarkFavoriteButton.addEventListener("click", () => {
        setFavorite(activeFolder, false);
    });

    uploadButton.addEventListener("click", () => {
        activeMenu = {
            "name": "Upload file",
            "hide": hideUploadMenu,
            "show": showUploadMenu
        }
        activeMenu.show();
    })

    selectFileButton.addEventListener("click", () => {
        fileInput.click();
    })

    // Execute
    getFolder(root);



    // Upload
    const CHUNK_SIZE = 64 * 1024 * 1024; // 64MiB

    async function upload(file) {
        if (statusEl.classList.contains('hide')) {
            statusEl.classList.remove('hide');
        }
        if (progress.classList.contains('hide')) {
            progress.classList.remove('hide');
        }

        statusEl.textContent = "Init upload...";

        // 1) init
        const initRes = await fetch('/api/uploads/init', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                filename: file.name,
                size: file.size,
                chunkSize: CHUNK_SIZE
            })
        });
        if (!initRes.ok) throw new Error(await initRes.text());
        const init = await initRes.json();

        const uploadId = init.uploadId;
        const chunkSize = init.chunkSize;
        const totalChunks = init.totalChunks;

        // 2) send chunks
        for (let index = 0; index < totalChunks; index++) {
            const start = index * chunkSize;
            const end = Math.min(file.size, start + chunkSize);
            const blob = file.slice(start, end);

            statusEl.textContent = `Upload chunk ${index + 1}/${totalChunks}...`;

            // Retry simple (3 essais)
            let ok = false;
            for (let attempt = 1; attempt <= 3; attempt++) {
                const fd = new FormData();
                fd.append('index', index);
                fd.append('chunk', blob, `chunk-${index}`);

                const r = await fetch(`/api/uploads/${uploadId}/chunk`, { method: 'POST', credentials: 'same-origin', body: fd });
                if (r.ok) { ok = true; break; }
                await new Promise(res => setTimeout(res, 500 * attempt));
            }
            if (!ok) {
                statusEl.textContent = `Échec chunk ${index}.`;
                return;
            }

            progress.value = Math.floor(((index + 1) / totalChunks) * 100);
        }

        // 3) complete
        statusEl.textContent = "Finalisation...";
        const completeRes = await fetch(`/api/uploads/${uploadId}/complete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                filename: fileNameInput.value,
                root: root
            })
        });
        if (!completeRes.ok) throw new Error(await completeRes.text());
        const done = await completeRes.json();

        statusEl.textContent = `Terminé: ${done.filename} (${done.bytes} bytes)`;
    }

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        fileNameInput.value = file.name;
    });

    uploadFileButton.addEventListener("click", () => {
        const file = fileInput.files[0];
        if (!file) return;

        upload(file).then(() => {
            clearFiles();
            getFolder(root);
        });
    })
});
