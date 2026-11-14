<template>
  <main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 mb-1">จัดการประเภทสินค้า</h1>
        <p class="text-muted mb-0">เพิ่ม แก้ไข หรือลบประเภทสินค้า</p>
      </div>
      <span class="badge text-bg-primary rounded-pill">{{ categories.length }} ประเภท</span>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div v-if="notice" class="alert alert-success" role="status">{{ notice }}</div>

    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3">{{ editingId === null ? 'เพิ่มประเภทใหม่' : 'แก้ไขประเภทสินค้า' }}</h2>
        <form class="row g-2" @submit.prevent="saveCategory">
          <div class="col-sm">
            <label for="category-name" class="visually-hidden">ชื่อประเภทสินค้า</label>
            <input
              id="category-name"
              v-model.trim="categoryName"
              class="form-control"
              type="text"
              maxlength="100"
              placeholder="เช่น เครื่องดื่ม"
              required
            />
          </div>
          <div class="col-sm-auto d-flex gap-2">
            <button class="btn btn-primary" type="submit" :disabled="saving">
              {{ saving ? 'กำลังบันทึก…' : editingId === null ? 'เพิ่มประเภท' : 'บันทึกการแก้ไข' }}
            </button>
            <button
              v-if="editingId !== null"
              class="btn btn-outline-secondary"
              type="button"
              @click="resetForm"
            >
              ยกเลิก
            </button>
          </div>
        </form>
      </div>
    </section>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" class="text-center">รหัส</th>
              <th scope="col">ชื่อประเภทสินค้า</th>
              <th scope="col" class="text-end">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="3" class="py-4 text-center text-muted">กำลังโหลดข้อมูล…</td>
            </tr>
            <tr v-else-if="categories.length === 0">
              <td colspan="3" class="py-4 text-center text-muted">ยังไม่มีประเภทสินค้า</td>
            </tr>
            <tr v-for="category in categories" v-else :key="category.category_id">
              <td class="text-center">{{ category.category_id }}</td>
              <td>{{ category.category_name }}</td>
              <td class="text-end text-nowrap">
                <button
                  class="btn btn-sm btn-outline-primary me-2"
                  type="button"
                  @click="editCategory(category)"
                >
                  แก้ไข
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  type="button"
                  :disabled="saving"
                  @click="deleteCategory(category)"
                >
                  ลบ
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
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

    const editCategory = (category) => {
      categoryName.value = category.category_name;
      editingId.value = category.category_id;
      notice.value = "";
      error.value = "";
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
      saveCategory,
      editCategory,
      deleteCategory,
      resetForm,
    };
  },
};
</script>
