<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <h2 class="mb-1">📂 จัดการประเภทสินค้า</h2>
        <p class="text-muted mb-0">เพิ่ม แก้ไข หรือลบประเภทสินค้า</p>
      </div>
      <button class="btn btn-primary" type="button" @click="openAddModal">
        เพิ่มประเภทสินค้า +
      </button>
    </div>

    <div v-if="notice" class="alert alert-success" role="status">{{ notice }}</div>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary text-center">
          <tr>
            <th scope="col">รหัส</th>
            <th scope="col">ชื่อประเภทสินค้า</th>
            <th scope="col">การจัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="3" class="py-4 text-center text-muted">กำลังโหลดข้อมูล...</td>
          </tr>
          <tr v-else-if="categories.length === 0">
            <td colspan="3" class="py-4 text-center text-muted">ยังไม่มีประเภทสินค้า</td>
          </tr>
          <tr v-for="category in categories" v-else :key="category.category_id">
            <td class="text-center">{{ category.category_id }}</td>
            <td>{{ category.category_name }}</td>
            <td>
              <div class="text-center text-nowrap">
                <button class="btn btn-warning btn-sm me-2" type="button" @click="editCategory(category)">
                  <i class="bi bi-pencil-square"></i> แก้ไข
                </button>
                <button
                  class="btn btn-danger btn-sm"
                  type="button"
                  :disabled="saving"
                  @click="deleteCategory(category)"
                >
                  <i class="bi bi-trash3"></i> ลบ
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="categoryModalTitle" class="modal-title">
              {{ editingId === null ? 'เพิ่มประเภทสินค้าใหม่' : 'แก้ไขประเภทสินค้า' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCategory">
              <div class="mb-3">
                <label for="category-name" class="form-label">ชื่อประเภทสินค้า</label>
                <input
                  id="category-name"
                  v-model.trim="categoryName"
                  class="form-control"
                  type="text"
                  maxlength="100"
                  placeholder="เช่น เครื่องดื่ม"
                  required
                  autofocus
                />
              </div>
              <button class="btn btn-success w-100" type="submit" :disabled="saving">
                {{ saving ? 'กำลังบันทึก...' : editingId === null ? 'เพิ่มประเภทสินค้า' : 'บันทึกการแก้ไข' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, ref } from "vue";

const API_URL = "http://localhost/MK_Shop-68701129/php_api/api_catagorys.php";

export default {
  name: "CategoriesView",
  setup() {
    const categories = ref([]);
    const categoryName = ref("");
    const editingId = ref(null);
    const loading = ref(true);
    const saving = ref(false);
    const error = ref("");
    const notice = ref("");
    let modalInstance = null;

    const request = async (url, options) => {
      const response = await fetch(url, options);
      const result = await response.json();
      if (!response.ok || result.error) {
        throw new Error(result.error || "ดำเนินการไม่สำเร็จ");
      }
      return result;
    };

    const fetchCategories = async () => {
      error.value = "";
      try {
        const result = await request(API_URL);
        categories.value = result.data || result.categories || [];
      } catch (err) {
        error.value = `โหลดประเภทสินค้าไม่สำเร็จ: ${err.message}`;
      } finally {
        loading.value = false;
      }
    };

    const resetForm = () => {
      categoryName.value = "";
      editingId.value = null;
    };

    const openAddModal = () => {
      resetForm();
      notice.value = "";
      error.value = "";
      modalInstance = new window.bootstrap.Modal(document.getElementById("categoryModal"));
      modalInstance.show();
    };

    const editCategory = (category) => {
      categoryName.value = category.category_name;
      editingId.value = category.category_id;
      notice.value = "";
      error.value = "";
      modalInstance = new window.bootstrap.Modal(document.getElementById("categoryModal"));
      modalInstance.show();
    };

    const saveCategory = async () => {
      saving.value = true;
      error.value = "";
      notice.value = "";

      const formData = new FormData();
      formData.append("action", editingId.value === null ? "add" : "update");
      formData.append("category_name", categoryName.value);
      if (editingId.value !== null) {
        formData.append("category_id", editingId.value);
      }

      try {
        const result = await request(API_URL, { method: "POST", body: formData });
        notice.value = result.message || "บันทึกข้อมูลเรียบร้อย";
        modalInstance?.hide();
        resetForm();
        await fetchCategories();
      } catch (err) {
        error.value = `บันทึกไม่สำเร็จ: ${err.message}`;
      } finally {
        saving.value = false;
      }
    };

    const deleteCategory = async (category) => {
      if (!window.confirm(`ยืนยันการลบประเภท “${category.category_name}” หรือไม่?`)) return;

      saving.value = true;
      error.value = "";
      notice.value = "";
      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("category_id", category.category_id);

      try {
        const result = await request(API_URL, { method: "POST", body: formData });
        notice.value = result.message || "ลบข้อมูลเรียบร้อย";
        if (editingId.value === category.category_id) resetForm();
        await fetchCategories();
      } catch (err) {
        error.value = `ลบไม่สำเร็จ: ${err.message}`;
      } finally {
        saving.value = false;
      }
    };

    onMounted(fetchCategories);

    return {
      categories,
      categoryName,
      editingId,
      loading,
      saving,
      error,
      notice,
      openAddModal,
      saveCategory,
      editCategory,
      deleteCategory,
      resetForm,
    };
  },
};
</script>
