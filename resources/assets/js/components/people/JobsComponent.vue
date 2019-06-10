<template>
  <div>
    <form>
      <div class="card-body" v-for="(job,index) in jobs">
        <span class="float-right" style="cursor:pointer"
            @click="remove(index)"> 
            X
        </span>    
        <div class="form-group">
          <label>Job Title</label>
          <input type="text" class="form-control" placeholder="Job title" v-model="job.jobTitle">
          <label>Company</label>
          <input type="text" class="form-control" placeholder="Company" v-model="job.company">
        </div>  
      </div>  
    </form>
    <button  @click="addJob" class="btn btn-primary">Add Past Job</button>
   
  </div>
</template>
<script>
export default {
  data: function() {
    return {
      jobs: [{ }]
    }
  },

  methods:{
    addJob: function(){
      this.jobs.push({
        jobTitle: '',
        company: ''
      })
    },

    remove: function(index){
      this.jobs.splice(index,1);
    }
  }

}
</script>

