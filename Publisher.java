import java.util.*;
public class Publisher implements Subject {
    private final List<Observer> observers = new ArrayList<>();
    private String title;
    private String content;
    private String date;
    @Override
    public void addObserver(Observer observer){
        observers.add(observer);
    }
    @Override
    /**
     * 
     * @param observer 观察者
     */
    public void removeObserver(Observer observer){
        if(observers.contains(observer)) {
            observers.remove(observer);
        } else {
            System.out.println("没有" + observer + "这个元素哦！");
        }
    }
    @Override
    public void notifyObservers(){
        for(Observer observer:observers){
            observer.update(this);
        }
    }

    public void setNews(String title, String content, String date){
        this.title = title;
        this.content = content;
        this.date = date;
        notifyObservers();
    }

    // 属性getter
    public String getTitle(){
        return this.title;
    }
    public String getContent(){
        return this.content;
    }
    public String getDate(){
        return this.date;
    }
}

